<?php // app/api/auth/LoginApi.php

declare(strict_types=1);

/**
 * Handles user authentication, session creation, and returns a JSON response.
 * FINAL FIX: Added robust try-catch block to handle server errors gracefully.
 * @return string
 */
function handle_login(): string
{
    try {
        $data = input();

        // 1. Validate the input
        $rules = [
            'email' => 'required|email',
            'password' => 'required'
        ];
        $errors = validate($data, $rules);
        if (!empty($errors)) {
            return validation_error($errors);
        }

        // 2. Find the user by email
        $user = table('users')->where('email', '=', $data['email'])->first();

        // 3. Verify user exists and password is correct
        if (!$user || !verify_password($data['password'], $user['password'])) {
            return error('Invalid credentials. Please check your email and password.', 401);
        }

        // 4. Regenerate session ID to prevent session fixation attacks
        session_regenerate_id(true);

        // 5. Fetch user roles and permissions using the Query Builder
        $roles = table('roles')
            ->select('roles.name')
            ->join('user_roles', 'roles.id', '=', 'user_roles.role_id')
            ->where('user_roles.user_id', '=', $user['id'])
            ->get();

        // Permissions from roles
        $permissions_from_roles = table('permissions')
            ->select('permissions.slug')
            ->join('role_permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->join('user_roles', 'role_permissions.role_id', '=', 'user_roles.role_id')
            ->where('user_roles.user_id', '=', $user['id'])
            ->get();

        // Direct user permissions
        $direct_permissions = table('permissions')
            ->select('permissions.slug')
            ->join('user_permissions', 'permissions.id', '=', 'user_permissions.permission_id')
            ->where('user_permissions.user_id', '=', $user['id'])
            ->get();

        // Combine role and direct permissions, ensuring uniqueness (mimicking UNION)
        $merged_permissions = array_merge($permissions_from_roles, $direct_permissions);
        $unique_slugs = [];
        $permissions = [];
        foreach ($merged_permissions as $p) {
            if (!in_array($p['slug'], $unique_slugs)) {
                $unique_slugs[] = $p['slug'];
                $permissions[] = $p;
            }
        }


        // 6. Store essential data in the session
        session_put('user_id', $user['id']);
        session_put('tenant_id', $user['tenant_id']);
        session_put('user_name', $user['name']);
        session_put('user_email', $user['email']);
        session_put('is_app_admin', (bool) $user['is_app_admin']);
        session_put('roles', array_column($roles, 'name'));
        session_put('permissions', array_column($permissions, 'slug'));
        session_put('login_time', time());

        // 7. Return a success response with user data (excluding password)
        unset($user['password']);

        return success([
            'user' => $user
        ], 'Login successful.');

    } catch (Throwable $e) {
        // Log the detailed error for the developer
        write_log("Login API Error: " . $e->getMessage() . "\n" . $e->getTraceAsString(), 'critical');

        // Return a safe error message to the user, but include details if in debug mode
        $message = 'An internal server error occurred. Please try again later.';
        if (is_debug()) {
            $message = $e->getMessage();
        }
        return error($message, 500);
    }
}