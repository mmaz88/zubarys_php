<?php /** * core/response.php - Response Handler Functions * * Provides helpers for building and sending HTTP responses. */
declare(strict_types=1);

/**
 * Set a single response header.
 *
 * @param string $name The name of the header.
 * @param string $value The value of the header.
 * @param bool $replace Whether to replace a previous similar header.
 */
function header_set(string $name, string $value, bool $replace = true): void
{
    if (!headers_sent()) {
        header("{$name}: {$value}", $replace);
    }
}

/**
 * Set multiple response headers from an associative array.
 *
 * @param array<string, string> $headers
 */
function headers_set(array $headers): void
{
    foreach ($headers as $name => $value) {
        header_set($name, $value);
    }
}

/**
 * Set the HTTP response status code.
 *
 * @param int $code The HTTP status code.
 * @return int The status code set.
 */
function status(int $code): int
{
    if (!headers_sent()) {
        http_response_code($code);
    }
    return http_response_code(); // Return the actual current code
}

/**
 * Return a JSON response.
 *
 * @param mixed $data The data to encode.
 * @param int $status The HTTP status code.
 * @param array<string, string> $headers Additional headers.
 * @return string The JSON-encoded string.
 */
function json_response(mixed $data, int $status = 200, array $headers = []): string
{
    status($status);
    header_set('Content-Type', 'application/json; charset=UTF-8');
    if (!empty($headers)) {
        headers_set($headers);
    }

    // Set a default error structure in case of encoding failure
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        status(500);
        return json_encode([
            'success' => false,
            'message' => 'JSON encoding failed: ' . json_last_error_msg()
        ]);
    }

    return $json;
}

/**
 * Return a standardized success JSON response.
 *
 * @param mixed|null $data The payload data.
 * @param string $message A success message.
 * @param int $status The HTTP status code.
 * @return string The JSON response.
 */
function success(mixed $data = null, string $message = 'Success', int $status = 200): string
{
    $response = [
        'success' => true,
        'message' => $message
    ];
    if ($data !== null) {
        $response['data'] = $data;
    }
    return json_response($response, $status);
}

/**
 * Return a standardized error JSON response.
 *
 * @param string $message An error message.
 * @param int $status The HTTP status code.
 * @param mixed|null $errors Additional error details.
 * @return string The JSON response.
 */
function error(string $message = 'Error', int $status = 400, mixed $errors = null): string
{
    $response = [
        'success' => false,
        'message' => $message
    ];
    if ($errors !== null) {
        $response['errors'] = $errors;
    }
    return json_response($response, $status);
}

/**
 * Return a standardized validation error response.
 *
 * @param mixed $errors The validation errors.
 * @param string $message The main error message.
 * @return string The JSON response.
 */
function validation_error(mixed $errors, string $message = 'Validation failed'): string
{
    return error($message, 422, $errors);
}

/**
 * Return an HTML response.
 *
 * @param string $content The HTML content.
 * @param int $status The HTTP status code.
 * @param array<string, string> $headers Additional headers.
 * @return string The HTML content.
 */
function html_response(string $content, int $status = 200, array $headers = []): string
{
    status($status);
    header_set('Content-Type', 'text/html; charset=UTF-8');
    if (!empty($headers)) {
        headers_set($headers);
    }
    return $content;
}

/**
 * Renders a view with an optional layout.
 *
 * @param string $template The path to the view file (e.g., 'home', 'about').
 * @param array<string, mixed> $data Data to be extracted into variables for the view.
 * @param string|null $layout The path to the layout file.
 * @return string The fully rendered HTML or JSON response.
 * @throws Exception if view or layout files are not found.
 */
function view(string $template, array $data = [], ?string $layout = null): string
{
    $view_file = APP_PATH . '/views/' . str_replace('.', '/', $template) . '.php';

    if (!file_exists($view_file)) {
        // It's better to handle this gracefully, perhaps with a logging mechanism.
        // For now, we'll throw an exception as in the original code.
        // write_log("View file not found: {$view_file}", 'error');
        throw new Exception("View file not found: {$template}");
    }

    ob_start();
    try {
        extract($data);
        require $view_file;
    } catch (Throwable $e) {
        ob_end_clean();
        // write_log("Error in view file '{$view_file}': " . $e->getMessage(), 'error');
        throw $e;
    }
    $content = ob_get_clean();

    // If a layout is specified, wrap the content in it.
    if ($layout) {
        $layout_file = APP_PATH . '/views/' . str_replace('.', '/', $layout) . '.php';
        if (!file_exists($layout_file)) {
            // write_log("Layout file not found: {$layout_file}", 'error');
            throw new Exception("Layout file '{$layout}' not found.");
        }
        $data['content'] = $content;
        ob_start();
        try {
            extract($data);
            require $layout_file;
        } catch (Throwable $e) {
            ob_end_clean();
            // write_log("Error in layout file '{$layout_file}': " . $e->getMessage(), 'error');
            throw $e;
        }
        return ob_get_clean();
    }

    // Otherwise, return only the raw content.
    return $content;
}