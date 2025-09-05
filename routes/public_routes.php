<?php // routes/public_routes.php
declare(strict_types=1);

// Public facing pages that do not require authentication

/**
 * Home Page
 * If the user is already logged in, they are redirected to the dashboard.
 * Otherwise, the public-facing home page is shown.
 */
get('/', function () {
    if (session_has('user_id')) {
        return redirect('/dashboard');
    }
    return view('home', ['title' => 'Welcome'], 'layout.public');
});

/**
 * Login Page
 * If the user is already logged in, redirect them to the dashboard.
 */
get('/login', function () {
    if (session_has('user_id')) {
        return redirect('/dashboard');
    }
    return view('login', ['title' => 'Sign In'], 'layout.public');
});

/**
 * Register Page (Placeholder)
 * If the user is already logged in, redirect them to the dashboard.
 */
get('/register', function () {
    if (session_has('user_id')) {
        return redirect('/dashboard');
    }
    return view('register', ['title' => 'Create Account'], 'layout.public');
});