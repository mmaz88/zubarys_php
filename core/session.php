<?php
// core/session.php - Session Management
/**
 * Session management functions
 */

/**
 * Get session value
 */
function session($key = null, $default = null)
{
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    if ($key === null) {
        return $_SESSION;
    }

    return $_SESSION[$key] ?? $default;
}

/**
 * Set session value
 */
function session_put($key, $value)
{
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    $_SESSION[$key] = $value;
    return $value;
}

/**
 * Check if session has key
 */
function session_has($key)
{
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    return isset($_SESSION[$key]);
}

/**
 * Remove session key
 */
function session_forget($key)
{
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    unset($_SESSION[$key]);
}

/**
 * Flush all session data
 */
function session_flush()
{
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    $_SESSION = [];
    return session_destroy();
}

/**
 * Flash message to session
 */
function flash($key, $message = null)
{
    if ($message === null) {
        // Get flash message
        $flash_key = '_flash_' . $key;
        $message = session($flash_key);
        session_forget($flash_key);
        return $message;
    }

    // Set flash message
    session_put('_flash_' . $key, $message);
}

/**
 * Get and clear all flash messages
 */
function get_flash_messages()
{
    $flash_messages = [];

    foreach (session() as $key => $value) {
        if (strpos($key, '_flash_') === 0) {
            $flash_key = substr($key, 7); // Remove '_flash_' prefix
            $flash_messages[$flash_key] = $value;
            session_forget($key);
        }
    }

    return $flash_messages;
}





