<?php
/**
 * routes/role_permission_routes.php - Role and Permission Management Routes
 * FINAL FIX: Moved `require_once` inside each route's callback to resolve scope issues.
 */
declare(strict_types=1);

// --- WEB ROUTES ---

get('/roles', function () {
    return view('system.roles', [
        'title' => 'Roles & Permissions',
        'page_title' => 'Manage Roles & Permissions',
        'breadcrumbs' => [
            ['text' => 'Home', 'url' => '/dashboard'],
            ['text' => 'Core Systems'],
            ['text' => 'Roles & Permissions'],
        ],
    ], 'layout.main');
});

get('/permissions', function () {
    return view('system.permissions', [
        'title' => 'Permissions',
        'page_title' => 'Manage Permissions',
        'breadcrumbs' => [
            ['text' => 'Home', 'url' => '/dashboard'],
            ['text' => 'Core Systems'],
            ['text' => 'Permissions'],
        ],
    ], 'layout.main');
});

// --- API ROUTES ---

// API group for role CRUD operations
group(['prefix' => 'api/roles', 'middleware' => ['CorsMiddleware', 'AuthMiddleware']], function () {

    get('/', function () {
        require_once API_PATH . '/system/RoleApi.php';
        return handle_list_roles();
    });

    post('/', function () {
        require_once API_PATH . '/system/RoleApi.php';
        return handle_create_role();
    });

    get('/{id:\d+}', function (int $id) {
        // --- THIS IS THE FIX ---
        require_once API_PATH . '/system/RoleApi.php';
        return handle_get_role($id);
    });

    post('/{id:\d+}', function (int $id) {
        require_once API_PATH . '/system/RoleApi.php';
        return handle_update_role($id);
    });

    post('/{id:\d+}/delete', function (int $id) {
        require_once API_PATH . '/system/RoleApi.php';
        return handle_delete_role($id);
    });
});

// API group for permission CRUD operations
group(['prefix' => 'api/permissions', 'middleware' => ['CorsMiddleware', 'AuthMiddleware']], function () {

    get('/', function () {
        require_once API_PATH . '/system/PermissionApi.php';
        return handle_list_permissions();
    });

    post('/', function () {
        require_once API_PATH . '/system/PermissionApi.php';
        return handle_create_permission();
    });

    get('/{id:\d+}', function (int $id) {
        // --- THIS IS THE FIX ---
        require_once API_PATH . '/system/PermissionApi.php';
        return handle_get_permission($id);
    });

    post('/{id:\d+}', function (int $id) {
        require_once API_PATH . '/system/PermissionApi.php';
        return handle_update_permission($id);
    });

    post('/{id:\d+}/delete', function (int $id) {
        require_once API_PATH . '/system/PermissionApi.php';
        return handle_delete_permission($id);
    });
});