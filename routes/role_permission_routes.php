<?php
/**
 * routes/role_permission_routes.php - Role and Permission Management Routes
 */
declare(strict_types=1);

// --- WEB ROUTES ---
group(['middleware' => ['AuthMiddleware']], function () {
    get('/roles', function () {
        check_permission('roles.view');
        return view('system.roles.index', ['title' => 'Roles & Permissions', 'page_title' => 'Manage Roles & Permissions'], 'layout.main');
    });
    get('/roles/create', function () {
        check_permission('roles.create');
        return view('system.roles.create', ['title' => 'Create Role', 'page_title' => 'Create New Role'], 'layout.main');
    });
    get('/roles/{id:\d+}/edit', function (int $id) {
        check_permission('roles.edit');
        $role = table('roles')->where('id', '=', $id)->first();
        if (!$role) {
            return view('errors.404', ['title' => 'Role Not Found'], 'layout.main');
        }
        return view('system.roles.edit', ['title' => 'Edit Role: ' . h($role['name']), 'page_title' => 'Edit Role', 'role' => $role], 'layout.main');
    });
});

// --- API ROUTES ---
group(['prefix' => 'api/roles', 'middleware' => ['CorsMiddleware', 'AuthMiddleware']], function () {

    // THE FIX: This MUST be a POST route to work correctly with the DataTables server-side config.
    post('/', function () {
        check_permission('roles.view');
        require_once API_PATH . '/system/RoleApi.php';
        return handle_list_roles();
    });

    post('/create', function () {
        check_permission('roles.create');
        require_once API_PATH . '/system/RoleApi.php';
        return handle_create_role();
    });

    get('/{id:\d+}', function (int $id) {
        check_permission('roles.edit');
        require_once API_PATH . '/system/RoleApi.php';
        return handle_get_role($id);
    });

    post('/{id:\d+}', function (int $id) {
        check_permission('roles.edit');
        require_once API_PATH . '/system/RoleApi.php';
        return handle_update_role($id);
    });

    post('/{id:\d+}/delete', function (int $id) {
        check_permission('roles.delete');
        require_once API_PATH . '/system/RoleApi.php';
        return handle_delete_role($id);
    });
});