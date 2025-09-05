<?php /** * core/request.php - Request Handler Functions * * Provides helpers to access and interact with the current HTTP request. */
declare(strict_types=1);

/**
 * Get the HTTP request method.
 *
 * @return string The request method (e.g., 'GET', 'POST').
 */
function get_request_method(): string
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

/**
 * Get the request URI path.
 *
 * @return string The URI path (e.g., '/users/1').
 */
function get_request_uri(): string
{
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    return '/' . trim($uri, '/') ?: '/';
}

/**
 * Get all request input data from GET, POST, and JSON body.
 *
 * @param string|null $key The specific key to retrieve.
 * @param mixed|null $default A default value if the key is not found.
 * @return mixed An array of all input or the specific value.
 */
function input(?string $key = null, mixed $default = null): mixed
{
    static $input_data = null;
    if ($input_data === null) {
        $input_data = $_GET + $_POST;
        $content_type = headers('content-type') ?? '';
        if (str_contains($content_type, 'application/json')) {
            $json = json_decode(file_get_contents('php://input'), true);
            if (is_array($json)) {
                $input_data = array_merge($input_data, $json);
            }
        }
    }

    if ($key === null) {
        return $input_data;
    }
    return $input_data[$key] ?? $default;
}

/**
 * Get a specific query parameter from the URL ($_GET).
 *
 * @param string $key The key of the query parameter.
 * @param mixed|null $default A default value if the key is not found.
 * @return mixed
 */
function query_param(string $key, mixed $default = null): mixed
{
    return $_GET[$key] ?? $default;
}

/**
 * Get a specific value from the POST body ($_POST).
 *
 * @param string $key The key of the POST parameter.
 * @param mixed|null $default A default value if the key is not found.
 * @return mixed
 */
function post_param(string $key, mixed $default = null): mixed
{
    return $_POST[$key] ?? $default;
}

/**
 * Get an uploaded file from the request ($_FILES).
 *
 * @param string $key The key of the file input.
 * @return array|null The file array or null if not found.
 */
function uploaded_file(string $key): ?array
{
    return $_FILES[$key] ?? null;
}

/**
 * Check if the request has a specific input key.
 *
 * @param string $key The key to check.
 * @return bool True if the key exists in the input data.
 */
function has(string $key): bool
{
    return array_key_exists($key, input());
}

/**
 * Get all input except for a specified array of keys.
 *
 * @param array<string> $keys The keys to exclude.
 * @return array The filtered input data.
 */
function except(array $keys): array
{
    $input_data = input();
    foreach ($keys as $key) {
        unset($input_data[$key]);
    }
    return $input_data;
}

/**
 * Get only a specified array of keys from the input data.
 *
 * @param array<string> $keys The keys to include.
 * @return array The filtered input data.
 */
function only(array $keys): array
{
    $input_data = input();
    $result = [];
    foreach ($keys as $key) {
        if (array_key_exists($key, $input_data)) {
            $result[$key] = $input_data[$key];
        }
    }
    return $result;
}

/**
 * Get all request headers.
 *
 * @param string|null $key A specific header key to retrieve (case-insensitive).
 * @return array|string|null All headers or a specific header value.
 */
function headers(?string $key = null): array|string|null
{
    static $headers = null;
    if ($headers === null) {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (str_starts_with($name, 'HTTP_')) {
                $header_name = str_replace('_', '-', strtolower(substr($name, 5)));
                $headers[$header_name] = $value;
            }
        }
        // Add content type and length if available
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = $_SERVER['CONTENT_TYPE'];
        }
        if (isset($_SERVER['CONTENT_LENGTH'])) {
            $headers['content-length'] = $_SERVER['CONTENT_LENGTH'];
        }
    }

    if ($key === null) {
        return $headers;
    }
    return $headers[strtolower($key)] ?? null;
}

/**
 * Check if the request is an AJAX request.
 *
 * @return bool True if the request is AJAX.
 */
function is_ajax(): bool
{
    return strtolower(headers('x-requested-with') ?? '') === 'xmlhttprequest';
}

/**
 * Check if the request is asking for a JSON response.
 *
 * @return bool True if the request's Accept header includes 'application/json'.
 */
function wants_json(): bool
{
    $accept = headers('accept') ?? '';
    return str_contains($accept, 'application/json') || is_ajax();
}

/**
 * Get the client's IP address, considering proxies.
 *
 * @return string The client's IP address.
 */
function get_client_ip(): string
{
    $ip_keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
    foreach ($ip_keys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            $ip_list = explode(',', $_SERVER[$key]);
            $ip = trim(end($ip_list));
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Get the client's user agent string.
 *
 * @return string The user agent.
 */
function get_user_agent(): string
{
    return $_SERVER['HTTP_USER_AGENT'] ?? '';
}

/**
 * Check if the request is secure (HTTPS).
 *
 * @return bool True if the connection is secure.
 */
function is_secure(): bool
{
    return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
        || (strtolower(headers('x-forwarded-proto') ?? '') === 'https');
}

/**
 * Get the full current URL.
 *
 * @return string The current URL.
 */
function current_url(): string
{
    $protocol = is_secure() ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    return $protocol . '://' . $host . $uri;
}