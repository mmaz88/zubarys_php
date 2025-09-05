<?php /** * routes/user_routes.php - User Management Routes */

declare(strict_types=1);

// --- WEB ROUTES (for rendering pages) ---

// WEB ROUTE: Renders the main user management list page.
get('/users', function () {
    return view(
        'system.users.index',
        [
            'title' => 'Users',
            'page_title' => 'Manage Users',
        ],
        'layout.main'
    );
});

// WEB ROUTE: Renders the "Create User" page.
get('/users/create', function () {
    return view(
        'system.users.create',
        [
            'title' => 'Create User',
            'page_title' => 'Create New User',
        ],
        'layout.main'
    );
});

// WEB ROUTE: Renders the "View User" page.
get('/users/{id}', function (string $id) {
    // This route is now for viewing, edit has its own URL
    return view(
        'system.users.view',
        [
            'title' => 'View User',
            'page_title' => 'User Profile',
            'user_id' => $id,
        ],
        'layout.main'
    );
});


// WEB ROUTE: Renders the "Edit User" page.
get('/users/{id}/edit', function (string $id) {
    $user = table('users')->where('id', '=', $id)->first();
    if (!$user) {
        return view('errors.404', ['title' => 'User Not Found'], 'layout.main');
    }
    return view(
        'system.users.edit',
        [
            'title' => 'Edit User: ' . h($user['name']),
            'page_title' => 'Edit User',
            'user' => $user, // Pass the user data to the view
        ],
        'layout.main'
    );
});


// --- API ROUTES (for data operations) ---
group(['prefix' => 'api/users', 'middleware' => ['CorsMiddleware', 'AuthMiddleware']], function () {

    // POST /api/users/list -> Correct, dedicated route for DataTables server-side processing.
    post('/list', function () {
        require_once API_PATH . '/system/UserApi.php';
        return handle_list_users();
    });

    // POST /api/users -> For creating a new user.
    post('/', function () {
        require_once API_PATH . '/system/UserApi.php';
        return handle_create_user();
    });

    // GET /api/users/{id} -> For fetching a single user's data.
    get('/{id}', function (string $id) {
        require_once API_PATH . '/system/UserApi.php';
        return handle_get_user($id);
    });

    // POST /api/users/{id} -> For updating an existing user.
    post('/{id}', function (string $id) {
        require_once API_PATH . '/system/UserApi.php';
        return handle_update_user($id);
    });

    // POST /api/users/{id}/delete -> For deleting a user.
    post('/{id}/delete', function (string $id) {
        require_once API_PATH . '/system/UserApi.php';
        return handle_delete_user($id);
    });
});