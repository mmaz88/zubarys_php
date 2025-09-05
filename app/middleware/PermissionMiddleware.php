<?php
// app/middleware/PermissionMiddleware.php
declare(strict_types=1);

/**
 * A special middleware that checks if the user has a specific permission.
 * This is more advanced than the simple AuthMiddleware.
 * 
 * It's not used directly in the route file but is called by the execute_pipeline
 * when a permission is specified in a route definition.
 *
 * @param string $permission The permission slug to check (e.g., 'roles.view').
 * @return bool True to continue, or it echoes an error and exits.
 */
function check_permission(string $permission): bool
{
    // First, ensure the user is even logged in.
    if (!session_has('user_id')) {
        if (wants_json()) {
            echo error('Authentication required', 401);
            exit;
        }
        redirect('/login');
        exit;
    }

    // A Super Admin bypasses all permission checks.
    if (session('is_app_admin') === true) {
        return true;
    }

    // Check if the user's session permissions contain the required one.
    $user_permissions = session('permissions', []);
    if (in_array($permission, $user_permissions)) {
        return true; // User has permission, continue.
    }

    // If the check fails, block the request.
    if (wants_json()) {
        echo error('Forbidden. You do not have the required permission: ' . $permission, 403);
        exit;
    }

    // For web views, you might want to render a 403 error page.
    status(403);
    echo view('errors.403', ['title' => 'Forbidden'], 'layout.main');
    exit;
}