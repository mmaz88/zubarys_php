<?php // app/api/system/TenantApi.php

declare(strict_types=1);

/**
 * A private helper to determine what actions the current session user can perform on a given tenant record.
 */
function check_tenant_permissions(array $tenant): array
{
    $isSuperAdmin = session('is_app_admin');
    $isTenantAdmin = session('is_tenant_admin');
    $currentUserTenantId = session('tenant_id');

    return [
        'edit' => $isSuperAdmin || ($isTenantAdmin && $tenant['id'] === $currentUserTenantId),
        'delete' => $isSuperAdmin, // Only Super Admins can delete
    ];
}

/**
 * Handles listing tenants with server-side processing and permission injection.
 */
function handle_list_tenants(): string
{
    $baseQuery = table('tenants');

    // If the user is not a Super Admin, they can only see their own tenant.
    if (!session('is_app_admin')) {
        $baseQuery->where('id', '=', session('tenant_id'));
    }

    $config = [
        'base_query' => $baseQuery,
        'searchable_columns' => ['name', 'domain'],
        'sortable_columns' => ['name', 'domain', 'status', 'created_at'],
        'default_sort' => ['column' => 'created_at', 'direction' => 'DESC'],
    ];

    $jsonResponse = api_list_handler($config);
    $response = json_decode($jsonResponse, true);

    // Inject the 'can' object for UI permissions
    if (isset($response['data'])) {
        foreach ($response['data'] as &$tenant) {
            $tenant['can'] = check_tenant_permissions($tenant);
        }
    }

    return json_encode($response);
}


/**
 * Handles creating a new tenant. (Super Admin only)
 */
function handle_create_tenant(): string
{
    // Security Check: Only Super Admins can create tenants.
    if (!session('is_app_admin')) {
        return error('Forbidden. You do not have permission to create tenants.', 403);
    }

    try {
        $data = input();
        $rules = [
            'name' => 'required|min:3|max:100',
            'domain' => 'max:100|unique:tenants,domain',
            'status' => 'required',
        ];

        $errors = validate($data, $rules);
        if (!empty($errors)) {
            return validation_error($errors);
        }

        $userId = session('user_id');
        $tenantId = generate_uuidv7();
        $currentTime = date('Y-m-d H:i:s');

        $insertData = [
            'id' => $tenantId,
            'name' => sanitize($data['name']),
            'domain' => !empty($data['domain']) ? sanitize($data['domain']) : null,
            'status' => sanitize($data['status']),
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => $currentTime,
            'updated_at' => $currentTime,
        ];

        table('tenants')->insert($insertData);
        $newTenant = table('tenants')->where('id', '=', $tenantId)->first();

        return success($newTenant, 'Tenant created successfully.', 201);
    } catch (Throwable $e) {
        write_log("Tenant Create API Error: " . $e->getMessage(), 'critical');
        return error('An internal server error occurred.', 500);
    }
}

/**
 * Handles fetching a single tenant by its ID.
 */
function handle_get_tenant(string $id): string
{
    $tenant = table('tenants')->where('id', '=', $id)->first();
    if (!$tenant) {
        return error('Tenant not found', 404);
    }

    // Security Check: Super Admin or the admin of this tenant
    if (!session('is_app_admin') && session('tenant_id') !== $id) {
        return error('Forbidden.', 403);
    }

    return success($tenant);
}


/**
 * Handles updating an existing tenant.
 */
function handle_update_tenant(string $id): string
{
    try {
        $tenant = table('tenants')->where('id', '=', $id)->first();
        if (!$tenant) {
            return error('Tenant not found.', 404);
        }

        // Security Check: Must be Super Admin or the admin of this tenant
        if (!session('is_app_admin') && session('tenant_id') !== $id) {
            return error('Forbidden. You do not have permission to edit this tenant.', 403);
        }

        $data = input();
        $rules = [
            'name' => 'required|min:3|max:100',
            'domain' => "max:100|unique:tenants,domain,{$id},id",
            'status' => 'required',
        ];

        $errors = validate($data, $rules);
        if (!empty($errors)) {
            return validation_error($errors);
        }

        $updateData = [
            'name' => sanitize($data['name']),
            'domain' => !empty($data['domain']) ? sanitize($data['domain']) : null,
            'status' => sanitize($data['status']),
            'updated_by' => session('user_id'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        // A tenant admin should not be able to change their own status to disabled.
        if (!session('is_app_admin') && $data['status'] !== 'active') {
            return error('You cannot change the status of your own tenant.', 403);
        }


        table('tenants')->where('id', '=', $id)->update($updateData);

        $updatedTenant = table('tenants')->where('id', '=', $id)->first();
        return success($updatedTenant, 'Tenant updated successfully.');
    } catch (Throwable $e) {
        write_log("Tenant Update API Error: " . $e->getMessage(), 'critical');
        return error('An internal server error occurred.', 500);
    }
}


/**
 * Handles deleting a tenant. (Super Admin only)
 */
function handle_delete_tenant(string $id): string
{
    // Security Check: Only Super Admins can delete tenants.
    if (!session('is_app_admin')) {
        return error('Forbidden. You do not have permission to delete tenants.', 403);
    }

    try {
        if (!table('tenants')->where('id', '=', $id)->first()) {
            return error('Tenant not found.', 404);
        }

        $userCount = table('users')->where('tenant_id', '=', $id)->count();
        if ($userCount > 0) {
            return error('Cannot delete tenant. It has ' . $userCount . ' associated user(s).', 409);
        }

        $deletedRows = table('tenants')->where('id', '=', $id)->delete();
        if ($deletedRows > 0) {
            return success(null, 'Tenant deleted successfully.');
        }

        return error('Failed to delete tenant.', 500);
    } catch (Throwable $e) {
        write_log("Tenant Delete API Error: " . $e->getMessage(), 'critical');
        return error('An internal server error occurred.', 500);
    }
}