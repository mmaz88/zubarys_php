<?php // app/api/system/UserApi.php

declare(strict_types=1);

/**
 * A private helper to determine what actions the current session user can perform on a given user record.
 * @param array $user The user record being checked.
 * @return array An array of booleans, e.g., ['view' => true, 'edit' => false, 'delete' => true].
 */
function check_user_permissions(array $user): array
{
    $permissions = session('permissions', []);
    $currentUserId = session('user_id');
    $isSuperAdmin = session('is_app_admin');
    $currentUserTenantId = session('tenant_id');

    $can = [
        'view' => in_array('users.view', $permissions),
        'edit' => in_array('users.edit', $permissions),
        'delete' => in_array('users.delete', $permissions),
    ];

    // Business Logic Constraints:
    // 1. You cannot delete yourself.
    if ($user['id'] === $currentUserId) {
        $can['delete'] = false;
    }

    // 2. A Tenant Admin cannot edit or delete a Super Admin.
    if (!$isSuperAdmin && (bool) $user['is_app_admin']) {
        $can['edit'] = false;
        $can['delete'] = false;
    }

    // 3. A Tenant Admin cannot act on users outside their own tenant.
    if (!$isSuperAdmin && $user['tenant_id'] !== $currentUserTenantId) {
        $can['view'] = false;
        $can['edit'] = false;
        $can['delete'] = false;
    }

    return $can;
}


/**
 * Handles listing users for DataTables with server-side processing.
 * MODIFIED: Now injects a 'can' object into each user row for permission-based UI.
 */
function handle_list_users(): string
{
    $baseQuery = table('users')
        ->select([
            'users.id',
            'users.name',
            'users.email',
            'users.is_tenant_admin',
            'users.created_at',
            'tenants.name as tenant_name',
            'users.tenant_id',
            'users.is_app_admin'
        ])
        ->leftJoin('tenants', 'users.tenant_id', '=', 'tenants.id');

    if (!session('is_app_admin')) {
        $baseQuery->where('users.tenant_id', '=', session('tenant_id'));
    }

    $config = [
        'base_query' => $baseQuery,
        'searchable_columns' => ['users.name', 'users.email', 'tenants.name'],
        'sortable_columns' => [
            'name' => 'users.name',
            'tenant_name' => 'tenants.name',
            'is_tenant_admin' => 'users.is_tenant_admin',
            'created_at' => 'users.created_at'
        ],
        'default_sort' => ['column' => 'created_at', 'direction' => 'desc'],
    ];

    // We must intercept the data after it's fetched to add permissions.
    $jsonResponse = api_list_handler($config);
    $response = json_decode($jsonResponse, true);

    if (isset($response['data'])) {
        foreach ($response['data'] as &$user) {
            $user['can'] = check_user_permissions($user);
        }
    }

    return json_encode($response);
}


/**
 * Handles fetching a single user.
 * MODIFIED: Also injects the 'can' object for permissions.
 */
function handle_get_user(string $id): string
{
    try {
        $user = table('users')
            ->select(['users.*', 'tenants.name as tenant_name'])
            ->leftJoin('tenants', 'users.tenant_id', '=', 'tenants.id')
            ->where('users.id', '=', $id)
            ->first();

        if (!$user) {
            return error('User not found.', 404);
        }

        // Security check is now part of the permission helper.
        $permissions = check_user_permissions($user);
        if (!$permissions['view']) {
            return error('Forbidden. You do not have permission to view this user.', 403);
        }
        $user['can'] = $permissions;


        $roles = table('roles')
            ->select('roles.name')
            ->join('user_roles', 'roles.id', '=', 'user_roles.role_id')
            ->where('user_roles.user_id', '=', $id)
            ->orderBy('roles.name', 'ASC')
            ->get();
        $user['roles'] = array_column($roles, 'name');
        unset($user['password']);

        return success($user);
    } catch (Throwable $e) {
        write_log("Get User API Error: " . $e->getMessage(), 'critical');
        return error('An internal server error occurred.', 500);
    }
}

/**
 * Handles creating a new user.
 */
function handle_create_user(): string
{
    try {
        $data = input();
        $isSuperAdmin = session('is_app_admin');

        $rules = [
            'name' => 'required|min:3|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
        ];

        // THE FIX: Super admins must specify a tenant. For Tenant Admins, it's automatic.
        if ($isSuperAdmin) {
            $rules['tenant_id'] = 'required|exists:tenants,id';
        }

        $errors = validate($data, $rules);
        if (!empty($errors)) {
            return validation_error($errors);
        }

        // Determine the tenant ID automatically for non-super admins.
        $tenantId = $isSuperAdmin ? $data['tenant_id'] : session('tenant_id');
        if (!$isSuperAdmin && !$tenantId) {
            return error('You must belong to a tenant to create a user.', 403);
        }

        $userId = generate_uuidv7();
        $currentTime = date('Y-m-d H:i:s');
        $insertData = [
            'id' => $userId,
            'name' => sanitize($data['name']),
            'email' => sanitize($data['email']),
            'password' => hash_password($data['password']),
            'tenant_id' => $tenantId,
            'is_tenant_admin' => !empty($data['is_tenant_admin']),
            'created_by' => session('user_id'),
            'updated_by' => session('user_id'),
            'created_at' => $currentTime,
            'updated_at' => $currentTime,
        ];
        table('users')->insert($insertData);
        $newUser = table('users')->where('id', '=', $userId)->first();
        unset($newUser['password']);
        return success($newUser, 'User created successfully.', 201);
    } catch (Throwable $e) {
        write_log("User Create API Error: " . $e->getMessage(), 'critical');
        return error('An internal server error occurred.', 500);
    }
}


/**
 * Handles updating an existing user.
 */
function handle_update_user(string $id): string
{
    try {
        $data = input();
        $isSuperAdmin = session('is_app_admin');

        $userToUpdate = table('users')->where('id', '=', $id)->first();
        if (!$userToUpdate) {
            return error('User not found.', 404);
        }

        // Security Check: Tenant admins can only edit users in their own tenant.
        if (!$isSuperAdmin && $userToUpdate['tenant_id'] !== session('tenant_id')) {
            return error('Forbidden. You do not have permission to edit this user.', 403);
        }

        $rules = [
            'name' => 'required|min:3|max:100',
            'email' => "required|email|unique:users,email,{$id},id",
        ];

        if ($isSuperAdmin) {
            $rules['tenant_id'] = 'required|exists:tenants,id';
        }

        if (!empty($data['password'])) {
            $rules['password'] = 'min:8|confirmed';
        }

        $errors = validate($data, $rules);
        if (!empty($errors)) {
            return validation_error($errors);
        }

        $tenantId = $isSuperAdmin ? $data['tenant_id'] : $userToUpdate['tenant_id'];

        $updateData = [
            'name' => sanitize($data['name']),
            'email' => sanitize($data['email']),
            'tenant_id' => $tenantId,
            'is_tenant_admin' => !empty($data['is_tenant_admin']),
            'updated_by' => session('user_id'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if (!empty($data['password'])) {
            $updateData['password'] = hash_password($data['password']);
        }
        table('users')->where('id', '=', $id)->update($updateData);
        $updatedUser = table('users')->where('id', '=', $id)->first();
        unset($updatedUser['password']);
        return success($updatedUser, 'User updated successfully.');
    } catch (Throwable $e) {
        write_log("User Update API Error: " . $e->getMessage(), 'critical');
        return error('An internal server error occurred.', 500);
    }
}


/**
 * Handles deleting a user.
 */
function handle_delete_user(string $id): string
{
    try {
        if ($id === session('user_id')) {
            return error('You cannot delete your own account.', 409);
        }
        $userToDelete = table('users')->where('id', '=', $id)->first();
        if (!$userToDelete) {
            return error('User not found.', 404);
        }

        if (!session('is_app_admin') && $userToDelete['tenant_id'] !== session('tenant_id')) {
            return error('Forbidden. You do not have permission to delete this user.', 403);
        }

        $deletedRows = table('users')->where('id', '=', $id)->delete();
        if ($deletedRows > 0) {
            return success(null, 'User deleted successfully.');
        }
        return error('Failed to delete user.', 500);
    } catch (Throwable $e) {
        write_log("User Delete API Error: " . $e->getMessage(), 'critical');
        return error('An internal server error occurred.', 500);
    }
}