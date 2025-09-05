<?php
// app/api/system/TenantApi.php
declare(strict_types=1);

/**
 * Handles listing tenants with server-side pagination, searching, and sorting.
 * MODIFIED: Now handles separate 'search' and 'status' filters.
 */
function handle_list_tenants(): string
{
    $config = [
        'base_query' => table('tenants'),

        'searchable_columns' => ['name', 'domain'],

        'sortable_columns' => ['name', 'domain', 'status', 'created_at'],

        'default_sort' => ['column' => 'created_at', 'direction' => 'DESC'],

        'filter_handlers' => [
            'status' => function ($query, $value) {
                if (!empty($value)) {
                    $query->where('status', '=', $value);
                }
            }
        ]
    ];

    return api_list_handler($config);
}

/**
 * Handles creating a new tenant.
 *
 * FINAL FIX: This version is now robust. It explicitly builds the array
 * for insertion, ignoring any extraneous data from the input (like an empty 'id' field).
 * This prevents database errors related to the primary key on create operations.
 */
/**
 * Handles creating a new tenant.
 *
 * FINAL FIX: This version is now robust. It explicitly builds the array
 * for insertion, ignoring any extraneous data from the input (like an empty 'id' field).
 * This prevents database errors related to the primary key on create operations.
 */
function handle_create_tenant(): string
{
    try {
        $data = input();
        $rules = [
            'name' => 'required|min:3|max:100',
            'domain' => 'max:100', // Domain is optional
            'status' => 'required',
        ];
        $errors = validate($data, $rules);
        if (!empty($errors)) {
            return validation_error($errors);
        }

        $userId = session('user_id');
        $tenantId = generate_uuidv7();
        $currentTime = date('Y-m-d H:i:s');

        // ==========================================================
        // THE FIX: Create a new, clean array for the insert operation.
        // This ensures no unwanted fields (like an empty 'id' from the form)
        // are passed to the database query, resolving the 500 error.
        // ==========================================================
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

        // This insert call will now succeed.
        table('tenants')->insert($insertData);

        $newTenant = table('tenants')->where('id', '=', $tenantId)->first();
        return success($newTenant, 'Tenant created successfully.', 201);

    } catch (Throwable $e) {
        if (str_contains($e->getMessage(), 'UNIQUE constraint failed') || str_contains($e->getMessage(), 'Duplicate entry')) {
            return validation_error(['domain' => 'The domain has already been taken.']);
        }

        write_log("Tenant Create API Error: " . $e->getMessage() . "\n" . $e->getTraceAsString(), 'critical');
        return error('An internal server error occurred.', 500);
    }
}

/**
 * Handles fetching a single tenant by its ID.
 */
function handle_get_tenant(string $id): string
{
    return api_get_handler([
        'table' => 'tenants',
        'id' => $id
        // App admins can view any tenant, so no specific security check is needed here beyond authentication.
    ]);
}

/**
 * Handles updating an existing tenant.
 */
function handle_update_tenant(string $id): string
{
    try {
        $data = input();
        $rules = [
            'name' => 'required|min:3|max:100',
            'domain' => 'max:100',
            'status' => 'required',
        ];

        $errors = validate($data, $rules);
        if (!empty($errors)) {
            return validation_error($errors);
        }

        if (!table('tenants')->where('id', '=', $id)->first()) {
            return error('Tenant not found.', 404);
        }

        table('tenants')->where('id', '=', $id)->update([
            'name' => sanitize($data['name']),
            'domain' => !empty($data['domain']) ? sanitize($data['domain']) : null,
            'status' => sanitize($data['status']),
            'updated_by' => session('user_id'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $updatedTenant = table('tenants')->where('id', '=', $id)->first();
        return success($updatedTenant, 'Tenant updated successfully.');
    } catch (Throwable $e) {
        if (str_contains($e->getMessage(), 'UNIQUE constraint failed') || str_contains($e->getMessage(), 'Duplicate entry')) {
            return validation_error(['domain' => 'The domain has already been taken.']);
        }
        write_log("Tenant Update API Error: " . $e->getMessage(), 'critical');
        return error('An internal server error occurred.', 500);
    }
}

/**
 * Handles deleting a tenant.
 */
function handle_delete_tenant(string $id): string
{
    try {
        if (!table('tenants')->where('id', '=', $id)->first()) {
            return error('Tenant not found.', 404);
        }

        // Prevent deletion if tenant has associated users
        $userCount = table('users')->where('tenant_id', '=', $id)->count();
        if ($userCount > 0) {
            return error('Cannot delete tenant. It has ' . $userCount . ' associated user(s).', 409); // 409 Conflict
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