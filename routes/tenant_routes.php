<?php // routes/tenant_routes.php

declare(strict_types=1);

// A helper function for this file to reduce code duplication
function can_manage_tenant(string $tenantId): bool
{
    if (session('is_app_admin')) {
        return true;
    }
    return session('is_tenant_admin') && session('tenant_id') === $tenantId;
}

// --- WEB ROUTES ---
group(['middleware' => ['AuthMiddleware']], function () {

    // Web route for the main tenant list view
    get('/tenants', function () {
        // All authenticated users can see the list, but the API will filter the data
        return view(
            'system.tenants.index',
            [
                'title' => 'Tenants',
                'page_title' => 'Manage Tenants',
                'is_super_admin' => session('is_app_admin'),
            ],
            'layout.main'
        );
    });

    // Web route for the create tenant page (Super Admin only)
    get('/tenants/create', function () {
        if (!session('is_app_admin')) {
            return view('errors.403', ['title' => 'Forbidden'], 'layout.main');
        }
        return view('system.tenants.create', ['title' => 'Create Tenant', 'page_title' => 'Create New Tenant'], 'layout.main');
    });

    // Web route for the edit tenant page
    get('/tenants/{id}/edit', function (string $id) {
        $tenant = table('tenants')->where('id', '=', $id)->first();
        if (!$tenant) {
            return view('errors.404', ['title' => 'Not Found'], 'layout.main');
        }
        // Security check: Must be Super Admin or the admin of this specific tenant
        if (!can_manage_tenant($id)) {
            return view('errors.403', ['title' => 'Forbidden'], 'layout.main');
        }
        return view(
            'system.tenants.edit',
            [
                'title' => 'Edit Tenant',
                'page_title' => 'Edit Tenant: ' . h($tenant['name']),
                'tenant' => $tenant
            ],
            'layout.main'
        );
    });
});


// --- API ROUTES ---
group(['prefix' => 'api/tenants', 'middleware' => ['CorsMiddleware', 'AuthMiddleware']], function () {

    // Lists tenants for DataTables (API filters by permission)
    post('/list', function () {
        require_once API_PATH . '/system/TenantApi.php';
        return handle_list_tenants();
    });

    // Creates a new tenant (API checks for Super Admin)
    post('/', function () {
        require_once API_PATH . '/system/TenantApi.php';
        return handle_create_tenant();
    });

    // Retrieves a single tenant (API checks permissions)
    get('/{id}', function (string $id) {
        require_once API_PATH . '/system/TenantApi.php';
        return handle_get_tenant($id);
    });

    // Updates an existing tenant (API checks permissions)
    post('/{id}', function (string $id) {
        require_once API_PATH . '/system/TenantApi.php';
        return handle_update_tenant($id);
    });

    // Deletes a tenant (API checks for Super Admin)
    post('/{id}/delete', function (string $id) {
        require_once API_PATH . '/system/TenantApi.php';
        return handle_delete_tenant($id);
    });
});