<?php // app/api/system/PermissionApi.php
declare(strict_types=1);

/**
 * Handles listing all permissions.
 */
function handle_list_permissions(): string
{
    try {
        $permissions = table('permissions')->orderBy('slug', 'ASC')->get();
        return success($permissions, 'Permissions fetched successfully.');
    } catch (Throwable $e) {
        write_log("Permission List API Error: " . $e->getMessage(), 'critical');
        return error('An internal server error occurred.', 500);
    }
}

/**
 * Handles creating a new permission.
 */
function handle_create_permission(): string
{
    try {
        $data = input();
        $rules = [
            'slug' => 'required|min:3|max:50|unique:permissions,slug',
            'description' => 'required|max:255',
        ];
        $errors = validate($data, $rules);
        if (!empty($errors)) {
            return validation_error($errors);
        }

        $insertData = [
            'slug' => sanitize($data['slug']),
            'description' => sanitize($data['description']),
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $permissionId = table('permissions')->insert($insertData);
        $newPermission = table('permissions')->where('id', '=', $permissionId)->first();

        return success($newPermission, 'Permission created successfully.', 201);
    } catch (Throwable $e) {
        write_log("Permission Create API Error: " . $e->getMessage(), 'critical');
        return error('An internal server error occurred.', 500);
    }
}

/**
 * Handles fetching a single permission by ID.
 */
function handle_get_permission(int $id): string
{
    return api_get_handler([
        'table' => 'permissions',
        'id' => $id
        // No security check needed; permissions are considered public data for an authenticated admin.
    ]);
}


/**
 * Handles updating an existing permission.
 */
function handle_update_permission(int $id): string
{
    try {
        $data = input();
        $rules = [
            'slug' => 'required|min:3|max:50',
            'description' => 'required|max:255',
        ];
        $errors = validate($data, $rules);
        if (!empty($errors)) {
            return validation_error($errors);
        }

        if (!table('permissions')->where('id', '=', $id)->first()) {
            return error('Permission not found.', 404);
        }

        $existing = table('permissions')
            ->where('slug', '=', $data['slug'])
            ->where('id', '!=', $id)
            ->first();
        if ($existing) {
            return validation_error(['slug' => 'This slug is already in use by another permission.']);
        }

        $updateData = [
            'slug' => sanitize($data['slug']),
            'description' => sanitize($data['description']),
        ];

        table('permissions')->where('id', '=', $id)->update($updateData);
        $updatedPermission = table('permissions')->where('id', '=', $id)->first();

        return success($updatedPermission, 'Permission updated successfully.');
    } catch (Throwable $e) {
        write_log("Permission Update API Error: " . $e->getMessage(), 'critical');
        return error('An internal server error occurred.', 500);
    }
}

/**
 * Handles deleting a permission.
 */
function handle_delete_permission(int $id): string
{
    try {
        if (!table('permissions')->where('id', '=', $id)->first()) {
            return error('Permission not found.', 404);
        }

        // Note: Associated role_permissions are deleted automatically by the database foreign key constraint (ON DELETE CASCADE)
        $deletedRows = table('permissions')->where('id', '=', $id)->delete();
        if ($deletedRows > 0) {
            return success(null, 'Permission deleted successfully.');
        }

        return error('Failed to delete permission.', 500);
    } catch (Throwable $e) {
        write_log("Permission Delete API Error: " . $e->getMessage(), 'critical');
        return error('An internal server error occurred.', 500);
    }
}