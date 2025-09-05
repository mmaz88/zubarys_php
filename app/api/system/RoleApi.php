<?php // app/api/system/RoleApi.php

declare(strict_types=1);

/**
 * A private helper to determine what actions the current session user can perform on a given role record.
 * THIS FUNCTION IS NOW MORE ROBUST to handle roles without tenants (global roles).
 */
function check_role_permissions(array $role): array
{
    $permissions = session('permissions', []);
    $isSuperAdmin = session('is_app_admin');
    $currentUserTenantId = session('tenant_id');

    $can = [
        'edit' => in_array('roles.edit', $permissions),
        'delete' => in_array('roles.delete', $permissions),
    ];

    // Business Logic Constraints for Tenant Admins:
    if (!$isSuperAdmin) {
        // THE FIX: Use empty() to safely check for tenant_id. It handles both NULL and non-existent keys without warnings.
        // 1. Tenant Admins CANNOT edit or delete Global roles (where tenant_id is empty).
        if (empty($role['tenant_id'])) {
            $can['edit'] = false;
            $can['delete'] = false;
        }
        // 2. Tenant Admins CANNOT edit or delete roles belonging to OTHER tenants.
        if (!empty($role['tenant_id']) && $role['tenant_id'] !== $currentUserTenantId) {
            $can['edit'] = false;
            $can['delete'] = false;
        }
    }

    // You cannot delete a role that is assigned to users.
    if ((int) ($role['user_count'] ?? 0) > 0) {
        $can['delete'] = false;
    }

    return $can;
}

/**
 * Handles listing roles with tenancy and user counts.
 * MODIFIED: Correctly handles both DataTables and generic API responses to inject permissions.
 */
function handle_list_roles(): string
{
    $baseQuery = table('roles')
        ->select([
            'roles.*',
            '(SELECT COUNT(*) FROM user_roles WHERE user_roles.role_id = roles.id) as user_count',
            'tenants.name as tenant_name'
        ])
        ->leftJoin('tenants', 'roles.tenant_id', '=', 'tenants.id');

    if (!session('is_app_admin')) {
        $tenantId = session('tenant_id');
        $baseQuery->where(function ($q) use ($tenantId) {
            $q->where('roles.tenant_id', '=', $tenantId)
                ->orWhereNull('roles.tenant_id');
        });
    }

    $config = [
        'base_query' => $baseQuery,
        'searchable_columns' => ['roles.name', 'roles.description', 'tenants.name'],
        'sortable_columns' => ['name', 'tenant_name', 'user_count'],
        'default_sort' => ['column' => 'name', 'direction' => 'ASC'],
    ];

    $jsonResponse = api_list_handler($config);
    $response = json_decode($jsonResponse, true);

    // THE FIX: This logic now correctly finds the array of roles regardless of response type.
    $roles_array = null;
    if (isset($response['draw'])) { // DataTables response
        $roles_array = &$response['data'];
    } elseif (isset($response['data']['data'])) { // Generic paginated response
        $roles_array = &$response['data']['data'];
    }

    if ($roles_array !== null) {
        foreach ($roles_array as &$role) {
            $role['can'] = check_role_permissions($role);
        }
    }

    return json_encode($response);
}


/**
 * Handles fetching a single role and its permissions for the edit modal.
 */
function handle_get_role(int $id): string
{
    $role = table('roles')->where('id', '=', $id)->first();
    if (!$role) {
        return error('Role not found.', 404);
    }

    if (!session('is_app_admin') && !empty($role['tenant_id']) && $role['tenant_id'] !== session('tenant_id')) {
        return error('Forbidden.', 403);
    }

    $permissionIds = table('role_permissions')
        ->where('role_id', '=', $id)
        ->get();

    $role['permissions'] = array_column($permissionIds, 'permission_id');
    return success($role);
}

/**
 * Handles creating a new role and assigning permissions.
 */
function handle_create_role(): string
{
    try {
        $data = input();
        $rules = [
            'name' => 'required|min:3|max:50',
            'description' => 'max:255',
        ];
        $errors = validate($data, $rules);
        if (!empty($errors)) {
            return validation_error($errors);
        }

        $tenantId = null;
        if (session('is_app_admin')) {
            $tenantId = !empty($data['tenant_id']) ? $data['tenant_id'] : null;
        } else {
            $tenantId = session('tenant_id');
            if (!$tenantId) {
                return error('You must belong to a tenant to create a role.', 403);
            }
        }

        $existingQuery = table('roles')->where('name', '=', $data['name']);
        if ($tenantId) {
            $existingQuery->where('tenant_id', '=', $tenantId);
        } else {
            $existingQuery->whereNull('tenant_id');
        }
        if ($existingQuery->first()) {
            return validation_error(['name' => 'A role with this name already exists for this scope (tenant or global).']);
        }

        db()->beginTransaction();

        $roleId = table('roles')->insert([
            'tenant_id' => $tenantId,
            'name' => sanitize($data['name']),
            'description' => sanitize($data['description'] ?? ''),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        if (!empty($data['permissions']) && is_array($data['permissions'])) {
            // ===== THE FIX IS HERE =====
            // Filter the array to ensure we only have valid, positive integers.
            // This prevents non-numeric values or '0' from causing a foreign key violation.
            $permissionIds = array_filter(array_map('intval', $data['permissions']), fn($id) => $id > 0);

            if (!empty($permissionIds)) {
                $permissionsToInsert = [];
                foreach ($permissionIds as $permissionId) {
                    $permissionsToInsert[] = ['role_id' => $roleId, 'permission_id' => $permissionId];
                }
                table('role_permissions')->insert($permissionsToInsert);
            }
            // ===== END OF FIX =====
        }

        db()->commit();

        $newRole = table('roles')->where('id', '=', $roleId)->first();
        return success($newRole, 'Role created successfully.', 201);

    } catch (Throwable $e) {
        db()->rollBack();
        write_log("Role Create API Error: " . $e->getMessage(), 'critical');
        return error('An internal server error occurred.', 500);
    }
}


/**
 * Handles updating an existing role and its permissions.
 */
function handle_update_role(int $id): string
{
    try {
        $data = input();
        $rules = [
            'name' => 'required|min:3|max:50',
            'description' => 'max:255',
        ];
        $errors = validate($data, $rules);
        if (!empty($errors)) {
            return validation_error($errors);
        }

        $role = table('roles')->where('id', '=', $id)->first();
        if (!$role) {
            return error('Role not found.', 404);
        }

        // Security Check
        $isSuperAdmin = session('is_app_admin');
        if (!$isSuperAdmin && $role['tenant_id'] !== session('tenant_id')) {
            return error('Forbidden. You do not have permission to edit this role.', 403);
        }
        if (!$isSuperAdmin && $role['tenant_id'] === null) {
            return error('Forbidden. Tenant admins cannot edit global roles.', 403);
        }

        $tenantId = $isSuperAdmin ? (!empty($data['tenant_id']) ? $data['tenant_id'] : null) : $role['tenant_id'];

        $existing = table('roles')
            ->where('name', '=', $data['name'])
            ->where('id', '!=', $id);
        if ($tenantId) {
            $existing->where('tenant_id', '=', $tenantId);
        } else {
            $existing->whereNull('tenant_id');
        }
        if ($existing->first()) {
            return validation_error(['name' => 'A role with this name already exists for this scope.']);
        }

        db()->beginTransaction();

        $updateData = [
            'name' => sanitize($data['name']),
            'description' => sanitize($data['description'] ?? ''),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($isSuperAdmin) {
            $updateData['tenant_id'] = $tenantId;
        }
        table('roles')->where('id', '=', $id)->update($updateData);

        // Resync permissions: delete all old, then insert all new.
        table('role_permissions')->where('role_id', '=', $id)->delete();
        if (!empty($data['permissions']) && is_array($data['permissions'])) {
            // ===== THE FIX IS HERE =====
            // Apply the same robust filtering as in the create function.
            $permissionIds = array_filter(array_map('intval', $data['permissions']), fn($id) => $id > 0);

            if (!empty($permissionIds)) {
                $permissionsToInsert = [];
                foreach ($permissionIds as $permissionId) {
                    $permissionsToInsert[] = ['role_id' => $id, 'permission_id' => $permissionId];
                }
                table('role_permissions')->insert($permissionsToInsert);
            }
            // ===== END OF FIX =====
        }

        db()->commit();

        $updatedRole = table('roles')->where('id', '=', $id)->first();
        return success($updatedRole, 'Role updated successfully.');

    } catch (Throwable $e) {
        db()->rollBack();
        write_log("Role Update API Error: " . $e->getMessage(), 'critical');
        return error('An internal server error occurred.', 500);
    }
}


/**
 * Handles deleting a role.
 */
function handle_delete_role(int $id): string
{
    try {
        $role = table('roles')->where('id', '=', $id)->first();
        if (!$role) {
            return error('Role not found.', 404);
        }

        // Security Check
        if (!session('is_app_admin') && $role['tenant_id'] !== session('tenant_id')) {
            return error('Forbidden.', 403);
        }
        if (!session('is_app_admin') && $role['tenant_id'] === null) {
            return error('Forbidden. Tenant admins cannot delete global roles.', 403);
        }

        $userCount = table('user_roles')->where('role_id', '=', $id)->count();
        if ($userCount > 0) {
            return error('Cannot delete role. It is assigned to ' . $userCount . ' user(s).', 409);
        }

        $deletedRows = table('roles')->where('id', '=', $id)->delete();
        if ($deletedRows > 0) {
            return success(null, 'Role deleted successfully.');
        }
        return error('Failed to delete role.', 500);

    } catch (Throwable $e) {
        write_log("Role Delete API Error: " . $e->getMessage(), 'critical');
        return error('An internal server error occurred.', 500);
    }
}