<?php
/**
 * routes/user_routes.php - User Management Routes
 */
declare(strict_types=1);

// --- WEB ROUTES (for rendering pages) ---

get('/users', function () {
    return view('system.users.index', ['title' => 'Users', 'page_title' => 'Manage Users'], 'layout.main');
});

// UPDATED: Route for the "Create User" page
get('/users/create', function () {
    $is_super_admin = session('is_app_admin');
    $current_admin_tenant_id = session('tenant_id');

    // Fetch tenants if super admin
    $tenants = $is_super_admin ? table('tenants')->orderBy('name')->get() : [];
    $tenant_options = array_column($tenants, 'name', 'id');

    // Fetch available roles
    $rolesQuery = table('roles');
    if (!$is_super_admin) {
        // Tenant admin sees global roles + their own tenant's roles
        $rolesQuery->where(function ($q) use ($current_admin_tenant_id) {
            $q->whereNull('tenant_id')->orWhere('tenant_id', '=', $current_admin_tenant_id);
        });
    }
    $available_roles = $rolesQuery->orderBy('name', 'ASC')->get();

    return view(
        'system.users.create',
        [
            'title' => 'Create User',
            'page_title' => 'Create New User',
            'is_super_admin' => $is_super_admin, // FIX: Pass this variable
            'tenant_options' => $tenant_options,
            'available_roles' => $available_roles,
            'user_role_ids' => [] // Empty for a new user
        ],
        'layout.main'
    );
});

get('/users/{id}', function (string $id) { /* ... unchanged ... */});

// UPDATED: Route for the "Edit User" page
get('/users/{id}/edit', function (string $id) {
    $user = table('users')->where('id', '=', $id)->first();
    if (!$user) {
        return view('errors.404', ['title' => 'User Not Found'], 'layout.main');
    }

    $is_super_admin = session('is_app_admin'); // FIX: Define this variable
    $current_admin_tenant_id = session('tenant_id');
    $user_tenant_id = $user['tenant_id'];

    // Fetch tenants if super admin
    $tenants = $is_super_admin ? table('tenants')->orderBy('name')->get() : [];
    $tenant_options = array_column($tenants, 'name', 'id');

    // Fetch available roles based on context
    $rolesQuery = table('roles');
    if ($is_super_admin && $user_tenant_id) {
        $rolesQuery->where(function ($q) use ($user_tenant_id) {
            $q->whereNull('tenant_id')->orWhere('tenant_id', '=', $user_tenant_id);
        });
    } else {
        $rolesQuery->where(function ($q) use ($current_admin_tenant_id) {
            $q->whereNull('tenant_id')->orWhere('tenant_id', '=', $current_admin_tenant_id);
        });
    }
    $available_roles = $rolesQuery->orderBy('name', 'ASC')->get();

    // Fetch currently assigned role IDs
    $user_role_ids = array_column(
        table('user_roles')->where('user_id', '=', $id)->get(),
        'role_id'
    );

    return view(
        'system.users.edit',
        [
            'title' => 'Edit User: ' . h($user['name']),
            'page_title' => 'Edit User',
            'user' => $user,
            'is_super_admin' => $is_super_admin, // FIX: Pass this variable
            'tenant_options' => $tenant_options,
            'available_roles' => $available_roles,
            'user_role_ids' => $user_role_ids
        ],
        'layout.main'
    );
});


// --- API ROUTES (for data operations) ---
group(['prefix' => 'api/users', 'middleware' => ['CorsMiddleware', 'AuthMiddleware']], function () {

    post('/list', function () {
        require_once API_PATH . '/system/UserApi.php';
        return handle_list_users();
    });

    // POST /api/users -> For creating a new user.
    post('/', function () {
        require_once API_PATH . '/system/UserApi.php';
        return handle_create_user();
    });

    get('/{id}', function (string $id) {
        require_once API_PATH . '/system/UserApi.php';
        return handle_get_user($id);
    });

    // POST /api/users/{id} -> For updating an existing user.
    post('/{id}', function (string $id) {
        require_once API_PATH . '/system/UserApi.php';
        return handle_update_user($id);
    });

    post('/{id}/delete', function (string $id) {
        require_once API_PATH . '/system/UserApi.php';
        return handle_delete_user($id);
    });
});