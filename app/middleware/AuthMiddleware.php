<?php

/**
 * app/middleware/AuthMiddleware.php
 *
 * Verifies if a user is authenticated by checking the session.
 * This script is executed by the router and must return a boolean.
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user ID is set in the session.
if (!isset($_SESSION['user_id'])) {
    if (wants_json()) {
        // For API requests, return a JSON error and terminate.
        echo error('Authentication required', 401);
        exit;
    } else {
        // For web requests, redirect to the login page. redirect() terminates execution.
        redirect('/login');
    }
    // NOTE: The lines above terminate execution, so any code after this point
    // in this block would be unreachable.
}

// If authenticated, return true to allow the request to proceed.
return true;