<?php /** * core/router.php - Routing Definition Helpers * * This file provides helper functions (get, post, group) to define routes. * It works with nikic/fast-route to create a dispatcher instance. */
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
                'middleware' => $route['middleware']
            ]);
        }
    });
}

/**
 * Registers a GET route.
 * @param string $uri The route URI pattern.
 * @param callable|string $callback The callback function or 'ApiFile::method' string.
 */
function get(string $uri, callable|string $callback): void
{
    add_route('GET', $uri, $callback);
}

/**
 * Registers a POST route.
 * @param string $uri The route URI pattern.
 * @param callable|string $callback The callback function or 'ApiFile::method' string.
 */
function post(string $uri, callable|string $callback): void
{
    add_route('POST', $uri, $callback);
}

// Add put(), patch(), delete() here if needed, following the same pattern.
/**
 * Creates a route group with common attributes like prefix and middleware.
 *
 * @param array<string, mixed> $attributes The group attributes ('prefix', 'middleware').
 * @param callable $callback The callback containing route definitions for the group.
 */
function group(array $attributes, callable $callback): void
{
    // Store the state of the parent group
    $previous_group = $GLOBALS['route_groups'];
    // Calculate the new group state
    $new_group = $previous_group;
    // **FIX**: Correctly concatenate string prefixes
    $new_prefix = trim($attributes['prefix'] ?? '', '/');
    if (!empty($new_prefix)) {
        $existing_prefix = trim($previous_group['prefix'] ?? '', '/');
        $new_group['prefix'] = !empty($existing_prefix) ? $existing_prefix . '/' . $new_prefix : $new_prefix;
    }
    // **FIX**: Correctly merge middleware arrays
    $new_middleware = (array) ($attributes['middleware'] ?? []);
    if (!empty($new_middleware)) {
        $existing_middleware = (array) ($previous_group['middleware'] ?? []);
        $new_group['middleware'] = array_unique(array_merge($existing_middleware, $new_middleware));
    }
    // Set the new state for the duration of the callback
    $GLOBALS['route_groups'] = $new_group;
    $callback();
    // Restore the parent group's state
    $GLOBALS['route_groups'] = $previous_group;
}

/**
 * Adds a route to the global collection, applying any group attributes.
 *
 * @param string $method The HTTP method.
 * @param string $uri The route URI.
 * @param callable|string $callback The route callback.
 */
function add_route(string $method, string $uri, callable|string $callback): void
{
    $prefix = trim($GLOBALS['route_groups']['prefix'] ?? '', '/');
    $path = trim($uri, '/');
    // Filter out empty parts to correctly handle root URIs ('/')
    $parts = array_filter([$prefix, $path]);
    $final_uri = '/' . implode('/', $parts);
    // Handle the specific case where the URI was just '/' within a group
    if (empty($parts) && !empty($prefix) && $uri === '/') {
        $final_uri = '/' . $prefix;
    } elseif (empty($parts) && $uri === '/') {
        $final_uri = '/';
    }
    $group_middleware = $GLOBALS['route_groups']['middleware'] ?? [];
    $GLOBALS['routes_collection'][] = [
        'method' => $method,
        'uri' => $final_uri,
        'callback' => $callback,
        'middleware' => $group_middleware
    ];
}

/**
 * Executes the middleware pipeline and the final route handler.
 * @return mixed The final response from the handler or middleware.
 */
function execute_pipeline(array $handler_info, array $params): mixed
{
    $callback = $handler_info['callback'];
    $middleware_stack = $handler_info['middleware'] ?? [];
    // The core handler is the last step in the pipeline
    $handler = fn(array $p) => execute_callback($callback, $p);
    if (!empty($middleware_stack)) {
        // Reverse the middleware to wrap them from outside-in
        $pipeline = array_reverse($middleware_stack);
        foreach ($pipeline as $middleware_name) {
            $handler = fn(array $p) => execute_middleware($middleware_name, $p, $handler);
        }
    }
    return $handler($params);
}

/**
 * Executes a middleware.
 * @return mixed The result of the next handler or a response from the middleware.
 */
function execute_middleware(string $middleware_name, array $params, callable $next): mixed
{
    $middleware_file = APP_PATH . '/middleware/' . $middleware_name . '.php';
    if (!file_exists($middleware_file)) {
        throw new Exception("Middleware not found: {$middleware_name}");
    }
    // The result of the require determines if the pipeline continues
    $result = require $middleware_file;
    // If middleware returns true, proceed to the next layer
    if ($result === true) {
        return $next($params);
    }
    // Middleware handled the response and exited, return null to stop the pipeline.
    return null;
}

/**
 * Executes the route's callback function.
 * UPDATED: Now handles type casting for route parameters.
 * @return mixed The result of the callback.
 * @throws Exception if the callback is not valid.
 */
function execute_callback(callable|string $callback, array $params = []): mixed
{
    if (is_callable($callback)) {
        // Get reflection info to understand parameter types
        $reflection = new ReflectionFunction($callback);
        $parameters = $reflection->getParameters();

        $args = [];
        $paramValues = array_values($params);

        foreach ($parameters as $index => $param) {
            $value = $paramValues[$index] ?? null;

            // Handle type casting based on parameter type hint
            if ($param->hasType()) {
                $type = $param->getType();
                if ($type instanceof ReflectionNamedType) {
                    $typeName = $type->getName();
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
                        // Add other types as needed
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