<?php // app/api/system/RoleApi.php
declare(strict_types=1);

/**
 * Handles listing roles with tenancy and user counts.
 * REWRITTEN:
 * - Superadmins can see all roles.
 * - Tenant users see their own roles plus global roles.
 * - Includes a 'user_count' for each role.
 * - Includes 'tenant_name' for context.
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

    // If the user is not a superadmin, restrict the view
    if (!session('is_app_admin')) {
        $tenantId = session('tenant_id');
        // Tenant users can see their own roles plus global roles.
        $baseQuery->where(function ($q) use ($tenantId) {
            $q->where('roles.tenant_id', '=', $tenantId)
                ->orWhere('roles.tenant_id', 'IS', null);
        });
    }

    $config = [
        'base_query' => $baseQuery,
        'searchable_columns' => ['roles.name', 'roles.description', 'tenants.name'],
        // CORRECTED: Ensure aliases and calculated columns sent by the frontend are whitelisted.
        'sortable_columns' => ['name', 'tenant_name', 'user_count'],
        'default_sort' => ['column' => 'name', 'direction' => 'ASC'],
    ];

    return api_list_handler($config);
}


/**
 * Handles creating a new role.
 * REWRITTEN:
 * - Superadmins can create global roles (by omitting tenant_id) or tenant-specific roles.
 * - Tenant admins can only create roles for their own tenant.
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
            // Superadmin can assign a role to a tenant or make it global
            $tenantId = !empty($data['tenant_id']) ? $data['tenant_id'] : null;
        } else {
            // Tenant admin can only create roles for their own tenant
            $tenantId = session('tenant_id');
            if (!$tenantId) {
                return error('You must belong to a tenant to create a role.', 403);
            }
        }

        // Check for uniqueness
        $existingQuery = table('roles')->where('name', '=', $data['name']);
        if ($tenantId) {
            $existingQuery->where('tenant_id', '=', $tenantId);
        } else {
            $existingQuery->whereNull('tenant_id');
        }
        if ($existingQuery->first()) {
            return validation_error(['name' => 'A role with this name already exists for the selected scope.']);
        }

        $insertData = [
            'tenant_id' => $tenantId,
            'name' => sanitize($data['name']),
            'description' => sanitize($data['description'] ?? ''),
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $roleId = table('roles')->insert($insertData);
        $newRole = table('roles')->where('id', '=', $roleId)->first();

        // Assign permissions
        if (!empty($data['permissions']) && is_array($data['permissions'])) {
            $permissionsToInsert = [];
            foreach ($data['permissions'] as $permissionId) {
                $permissionsToInsert[] = ['role_id' => $roleId, 'permission_id' => (int) $permissionId];
            }
            if (!empty($permissionsToInsert)) {
                table('role_permissions')->insert($permissionsToInsert);
            }
        }

        return success($newRole, 'Role created successfully.', 201);
    } catch (Throwable $e) {
        write_log("Role Create API Error: " . $e->getMessage(), 'critical');
        return error('An internal server error occurred.', 500);
    }
}

/**
 * Handles fetching a single role and its permissions.
 * REWRITTEN: Security check allows access if user is superadmin, role is global, or role belongs to user's tenant.
 */
function handle_get_role(int $id): string
{
    return api_get_handler([
        'table' => 'roles',
        'id' => $id,
        'security_check' => function ($role) {
            return session('is_app_admin') ||
                $role['tenant_id'] === null ||
                $role['tenant_id'] === session('tenant_id');
        },
        'relations' => [
            'permissions' => [
                'pivot_table' => 'role_permissions',
                'foreign_key' => 'role_id',
                'related_key' => 'permission_id'
            ]
        ]
    ]);
}

/**
 * Handles updating an existing role.
 * REWRITTEN:
 * - Superadmins can edit any role.
 * - Tenant admins can only edit their own tenant's roles, NOT global roles.
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
            return validation_error(['name' => 'A role with this name already exists for the selected scope.']);
        }

        $updateData = [
            'name' => sanitize($data['name']),
            'description' => sanitize($data['description'] ?? ''),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($isSuperAdmin) {
            $updateData['tenant_id'] = $tenantId;
        }

        table('roles')->where('id', '=', $id)->update($updateData);
        table('role_permissions')->where('role_id', '=', $id)->delete();
        if (!empty($data['permissions']) && is_array($data['permissions'])) {
            $permissionsToInsert = [];
            foreach ($data['permissions'] as $permissionId) {
                $permissionsToInsert[] = ['role_id' => $id, 'permission_id' => (int) $permissionId];
            }
            if (!empty($permissionsToInsert)) {
                table('role_permissions')->insert($permissionsToInsert);
            }
        }

        $updatedRole = table('roles')->where('id', '=', $id)->first();
        return success($updatedRole, 'Role updated successfully.');
    } catch (Throwable $e) {
        write_log("Role Update API Error: " . $e->getMessage(), 'critical');
        return error('An internal server error occurred.', 500);
    }
}

/**
 * Handles deleting a role.
 * REWRITTEN: Tenant admins cannot delete global roles.
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

        if ((int) $role['user_count'] > 0) {
            return error('Cannot delete role. It is assigned to ' . $role['user_count'] . ' user(s).', 409);
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