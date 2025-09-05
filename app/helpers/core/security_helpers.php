<?php // ================================
// app/helpers/security_helpers.php - Security Functions

/**
 * Security helper functions
 */
declare(strict_types=1);

/**
 * Generate CSRF token
 */
function csrf_token(): string
{
    if (!session_has('_csrf_token')) {
        session_put('_csrf_token', bin2hex(random_bytes(32)));
    }
    return session('_csrf_token');
}

/**
 * Verify CSRF token
 */
function verify_csrf(string $token): bool
{
    return hash_equals(session('_csrf_token', ''), $token);
}

/**
 * Hash password
 */
function hash_password(string $password): string
{
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password
 */
function verify_password(string $password, string $hash): bool
{
    return password_verify($password, $hash);
}

/**
 * Generate random string
 */
function random_string(int $length = 32): string
{
    // Ensure length is even for bin2hex
    return bin2hex(random_bytes($length / 2));
}

/**
 * Encrypt data using authenticated encryption (AES-256-GCM).
 *
 * IMPROVEMENT: Upgraded from AES-256-CBC to AES-256-GCM to provide authenticated
 * encryption, protecting against both tampering and padding oracle attacks.
 *
 * @param string $data The plaintext data to encrypt.
 * @param string|null $key The encryption key. Uses APP_KEY from .env if not provided.
 * @return string The base64-encoded encrypted data string.
 * @throws Exception If encryption key is not set or encryption fails.
 */
function encrypt(string $data, ?string $key = null): string
{
    $key = $key ?: env('APP_KEY');
    if (!$key) {
        throw new Exception('Encryption key not set.');
    }

    $iv = random_bytes(openssl_cipher_iv_length('aes-256-gcm'));
    $tag = ''; // Authentication tag will be filled by openssl_encrypt

    $encrypted = openssl_encrypt($data, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);

    if ($encrypted === false) {
        throw new Exception('Encryption failed.');
    }

    // Return IV, tag, and ciphertext together, base64-encoded
    return base64_encode($iv . $tag . $encrypted);
}

/**
 * Decrypt data encrypted with the corresponding encrypt() function (AES-256-GCM).
 *
 * IMPROVEMENT: Upgraded to support AES-256-GCM.
 *
 * @param string $encrypted_data The base64-encoded string from encrypt().
 * @param string|null $key The encryption key. Uses APP_KEY from .env if not provided.
 * @return string|false The original plaintext data, or false on failure.
 * @throws Exception If encryption key is not set.
 */
function decrypt(string $encrypted_data, ?string $key = null): string|false
{
    $key = $key ?: env('APP_KEY');
    if (!$key) {
        throw new Exception('Encryption key not set.');
    }

    $data = base64_decode($encrypted_data);
    $iv_length = openssl_cipher_iv_length('aes-256-gcm');
    $tag_length = 16;

    $iv = substr($data, 0, $iv_length);
    $tag = substr($data, $iv_length, $tag_length);
    $encrypted = substr($data, $iv_length + $tag_length);

    return openssl_decrypt($encrypted, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
}


/**
 * Rate limiting
 */
function rate_limit(string $key, int $max_attempts = 60, int $decay_minutes = 1): bool
{
    $cache_key = "rate_limit:{$key}";
    $attempts = cache_get($cache_key, 0);
    if ($attempts >= $max_attempts) {
        return false;
    }
    cache_put($cache_key, $attempts + 1, $decay_minutes * 60);
    return true;
}

/**
 * Get rate limit remaining attempts
 */
function rate_limit_remaining(string $key, int $max_attempts = 60): int
{
    $cache_key = "rate_limit:{$key}";
    $attempts = cache_get($cache_key, 0);
    return max(0, $max_attempts - $attempts);
}

/**
 * NEW: Sends a set of recommended security headers.
 *
 * WHY IT'S NEEDED: Helps protect against common web vulnerabilities like
 * cross-site scripting (XSS), clickjacking, and protocol downgrade attacks.
 * This should be called early in the application bootstrap process.
 */
function send_security_headers(): void
{
    if (headers_sent()) {
        return;
    }

    // Prevent Clickjacking
    header('X-Frame-Options: SAMEORIGIN');
    // Prevent XSS with modern browsers
    header('X-Content-Type-Options: nosniff');
    // Enforce HTTPS
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    // Control information sharing
    header('Referrer-Policy: no-referrer-when-downgrade');
    // A basic Content Security Policy (CSP). This should be configured for your app's specific needs.
    // header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:;");
}