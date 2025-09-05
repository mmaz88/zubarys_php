<?php // routes/auth.php
/**
 * Defines authentication-related API routes and logout functionality.
 */
declare(strict_types=1);

// API route group for authentication
group(['prefix' => 'api/auth'], function () {
    /**
     * Handles the login attempt.
     */
    post('/login', function () {
        require_once APP_PATH . '/api/auth/LoginApi.php';
        return handle_login();
    });
});

/**
 * Handles the logout action for web users.
 * This is a POST route to prevent CSRF attacks and requires authentication.
 */
post('/logout', function () {
    if (!session_has('user_id')) {
        return redirect('/login');
    }

    if (isset($_POST['_csrf_token']) && verify_csrf($_POST['_csrf_token'])) {
        session_flush();
        return redirect('/login');
    }

    return error('Invalid request.', 403);
});