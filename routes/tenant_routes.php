<?php


/**
 * routes/tenant_routes.php - Tenant Routes
 *
 * Defines all web and API routes related to tenant management.
 */
declare(strict_types=1);


// Add this to the end of a route file like routes/web.php for a quick test

get('/test-insert', function () {
    echo "<pre>";
    echo "Attempting a direct database insert...\n\n";

    try {
        // Prepare a minimal, valid data array.
        // We will only provide the fields that are NOT NULL and have no default value.
        $testData = [
            'id' => generate_uuidv7(), // The only truly required field
            'name' => 'Direct Insert Test @ ' . date('H:i:s'),
            // We will NOT provide `created_at` to let the database use its default.
        ];

        echo "Data to be inserted:\n";
        print_r($testData);
        echo "\n";

        // Execute the insert
        $lastInsertId = table('tenants')->insert($testData);

        // In SQLite, lastInsertId for a UUID primary key might return 0 or the rowid, not the UUID.
        // The real test is whether an exception was thrown.
        echo "SUCCESS!\n";
        echo "QueryBuilder::insert() executed without error.\n";
        echo "lastInsertId() returned: " . htmlspecialchars(print_r($lastInsertId, true)) . "\n\n";

        // Verify the data was actually inserted
        $insertedRecord = table('tenants')->where('id', '=', $testData['id'])->first();
        echo "Verified record from database:\n";
        print_r($insertedRecord);


    } catch (Throwable $e) {
        // If the insert fails, this block will execute and show the exact error.
        echo "--- FAILED ---\n\n";
        echo "An exception was caught:\n";
        echo "Error Type: " . get_class($e) . "\n";
        echo "Error Message: " . $e->getMessage() . "\n\n";
        echo "Stack Trace:\n";
        echo $e->getTraceAsString();
    }
    echo "</pre>";
});

// WEB ROUTE: Renders the main tenant management page.
// This view will contain the necessary HTML structure and JavaScript for the dynamic interface.
get('/tenants', function () {
    return view('system.tenants', [
        'title' => 'Tenants',
        'page_title' => 'Manage Tenants',
        'breadcrumbs' => [
            ['text' => 'Home', 'url' => '/dashboard'],
            ['text' => 'Core Systems'],
            ['text' => 'Tenants'],
        ],
    ], 'layout.main');
});


// API ROUTES: Grouped for tenant CRUD operations.
// These routes are protected by CORS and Authentication middleware.
group(['prefix' => 'api/tenants', 'middleware' => ['CorsMiddleware', 'AuthMiddleware']], function () {

    /**
     * GET /api/tenants
     * Lists tenants with server-side pagination, sorting, and searching.
     * Query Parameters:
     * - page (int): The current page number.
     * - per_page (int): Items per page.
     * - sort_by (string): Column to sort by.
     * - sort_dir (string): 'asc' or 'desc'.
     * - search (string): Search term.
     */
    get('/', function () {
        require_once API_PATH . '/system/TenantApi.php';
        return handle_list_tenants();
    });

    /**
     * POST /api/tenants
     * Creates a new tenant.
     */
    post('/', function () {
        require_once API_PATH . '/system/TenantApi.php';
        return handle_create_tenant();
    });

    /**
     * GET /api/tenants/{id}
     * Retrieves a single tenant's details for editing.
     */
    get('/{id}', function (string $id) {
        require_once API_PATH . '/system/TenantApi.php';
        return handle_get_tenant($id);
    });

    /**
     * POST /api/tenants/{id}
     * Updates an existing tenant.
     * (Uses POST to avoid needing a separate PUT helper in the router).
     */
    post('/{id}', function (string $id) {
        require_once API_PATH . '/system/TenantApi.php';
        return handle_update_tenant($id);
    });

    /**
     * POST /api/tenants/{id}/delete
     * Deletes a tenant.
     */
    post('/{id}/delete', function (string $id) {
        require_once API_PATH . '/system/TenantApi.php';
        return handle_delete_tenant($id);
    });
});