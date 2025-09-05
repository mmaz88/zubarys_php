<?php
/**
 * core/router.php - Routing Definition Helpers
 *
 * This file provides helper functions (get, post, group) to define routes.
 * It works with nikic/fast-route to create a dispatcher instance.
 */
declare(strict_types=1);

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;

// Global storage for route definitions and group attributes.
$GLOBALS['routes_collection'] = [];
$GLOBALS['route_groups'] = [];

/**
 * Creates a FastRoute dispatcher instance from a set of route definitions.
 *
 * @param callable $routeDefinitionCallback A closure that contains the route definitions.
 * @return Dispatcher
 */
function create_dispatcher(callable $routeDefinitionCallback): Dispatcher
{
    return simpleDispatcher(function (RouteCollector $r) use ($routeDefinitionCallback) {
        // Clear previous state and execute the callback to populate the routes
        $GLOBALS['routes_collection'] = [];
        $GLOBALS['route_groups'] = [];
        $routeDefinitionCallback();
        foreach ($GLOBALS['routes_collection'] as $route) {
            $r->addRoute($route['method'], $route['uri'], [
                'callback' => $route['callback'],
                'middleware' => $route['middleware'],
                'permission' => $route['permission'] ?? null, // Add permission to the handler info
            ]);
        }
    });
}

/**
 * Registers a GET route.
 * @param string $uri The route URI pattern.
 * @param callable|string $callback The callback function or 'ApiFile::method' string.
 * @param string|null $permission An optional permission required for this route.
 */
function get(string $uri, callable|string $callback, ?string $permission = null): void
{
    add_route('GET', $uri, $callback, $permission);
}

/**
 * Registers a POST route.
 * @param string $uri The route URI pattern.
 * @param callable|string $callback The callback function or 'ApiFile::method' string.
 * @param string|null $permission An optional permission required for this route.
 */
function post(string $uri, callable|string $callback, ?string $permission = null): void
{
    add_route('POST', $uri, $callback, $permission);
}

/**
 * Creates a route group with common attributes like prefix, middleware, and permissions.
 */
function group(array $attributes, callable $callback): void
{
    $previous_group = $GLOBALS['route_groups'];
    $new_group = $previous_group;

    $new_prefix = trim($attributes['prefix'] ?? '', '/');
    if (!empty($new_prefix)) {
        $existing_prefix = trim($previous_group['prefix'] ?? '', '/');
        $new_group['prefix'] = !empty($existing_prefix) ? $existing_prefix . '/' . $new_prefix : $new_prefix;
    }

    $new_middleware = (array) ($attributes['middleware'] ?? []);
    if (!empty($new_middleware)) {
        $existing_middleware = (array) ($previous_group['middleware'] ?? []);
        $new_group['middleware'] = array_unique(array_merge($existing_middleware, $new_middleware));
    }

    // Add group-level permission
    if (isset($attributes['permission'])) {
        $new_group['permission'] = $attributes['permission'];
    }

    $GLOBALS['route_groups'] = $new_group;
    $callback();
    $GLOBALS['route_groups'] = $previous_group;
}

/**
 * Adds a route to the global collection, applying any group attributes.
 */
function add_route(string $method, string $uri, callable|string $callback, ?string $permission = null): void
{
    $prefix = trim($GLOBALS['route_groups']['prefix'] ?? '', '/');
    $path = trim($uri, '/');
    $parts = array_filter([$prefix, $path]);
    $final_uri = '/' . implode('/', $parts);

    if (empty($parts) && !empty($prefix) && $uri === '/') {
        $final_uri = '/' . $prefix;
    } elseif (empty($parts) && $uri === '/') {
        $final_uri = '/';
    }

    $GLOBALS['routes_collection'][] = [
        'method' => $method,
        'uri' => $final_uri,
        'callback' => $callback,
        'middleware' => $GLOBALS['route_groups']['middleware'] ?? [],
        'permission' => $permission ?? $GLOBALS['route_groups']['permission'] ?? null,
    ];
}


/**
 * Executes the middleware pipeline and the final route handler.
 */
function execute_pipeline(array $handler_info, array $params): mixed
{
    // ===== THE FIX IS HERE =====
    // This pipeline is now smarter and handles both permissions and middleware.

    $callback = $handler_info['callback'];
    $permission = $handler_info['permission'] ?? null;
    $middleware_stack = $handler_info['middleware'] ?? [];

    // The core handler is the last step in the pipeline.
    $handler = fn(array $p) => execute_callback($callback, $p);

    // 1. Wrap the handler with all the NAMED middleware (e.g., 'CorsMiddleware').
    if (!empty($middleware_stack)) {
        $pipeline = array_reverse($middleware_stack);
        foreach ($pipeline as $middleware_name) {
            $handler = fn(array $p) => execute_middleware($middleware_name, $p, $handler);
        }
    }

    // 2. Wrap the entire stack with the PERMISSION check, if it exists.
    // This ensures the permission check runs first.
    if ($permission) {
        $handler = fn(array $p) => check_permission($permission) ? $handler($p) : null;
    }

    // 3. Execute the final, fully wrapped handler.
    return $handler($params);
    // ===== END OF FIX =====
}


/**
 * Executes a named middleware file.
 */
function execute_middleware(string $middleware_name, array $params, callable $next): mixed
{
    $middleware_file = APP_PATH . '/middleware/' . $middleware_name . '.php';
    if (!file_exists($middleware_file)) {
        // Throw an exception for a more explicit error in logs.
        throw new Exception("Middleware file not found: {$middleware_file}");
    }

    // The result of including the file determines if the pipeline continues.
    $result = require $middleware_file;

    // If middleware returns true, proceed to the next layer in the pipeline.
    if ($result === true) {
        return $next($params);
    }

    // Otherwise, the middleware has handled the response and exited.
    // Return null to stop the pipeline from continuing.
    return null;
}


/**
 * Executes the route's callback function.
 */
function execute_callback(callable|string $callback, array $params = []): mixed
{
    if (is_callable($callback)) {
        $reflection = new ReflectionFunction($callback);
        $parameters = $reflection->getParameters();
        $args = [];
        $paramValues = array_values($params);
        foreach ($parameters as $index => $param) {
            $value = $paramValues[$index] ?? null;
            if ($param->hasType()) {
                $type = $param->getType();
                if ($type instanceof ReflectionNamedType) {
                    $typeName = $type->getName();
                    if ($value !== null) { // Avoid casting null values
                        switch ($typeName) {
                            case 'int':
                                $value = (int) $value;
                                break;
                            case 'float':
                                $value = (float) $value;
                                break;
                            case 'bool':
                                $value = (bool) $value;
                                break;
                            case 'string':
                                $value = (string) $value;
                                break;
                        }
                    }
                }
            }
            $args[] = $value;
        }
        return call_user_func_array($callback, $args);
    }
    throw new Exception("Invalid route callback provided.");
}

/**
 * Redirects to a given URL.
 */
function redirect(string $url, int $status = 302): void
{
    header("Location: {$url}", true, $status);
    exit;
}