<?php
/**
 * core/kernel.php - Application Kernel
 *
 * This file contains the core request handling logic. It uses a single dispatcher
 * to handle all incoming web and API requests.
 */
declare(strict_types=1);

use FastRoute\Dispatcher;

/**
 * Handles the incoming HTTP request.
 *
 * This is the main entry point for the application logic after bootstrapping.
 */
function handle_request(): void
{
    $uri = get_request_uri();
    $httpMethod = get_request_method();

    // Support for method spoofing (e.g., using PUT/DELETE in forms)
    if ($httpMethod === 'POST' && !empty($_POST['_method'])) {
        $httpMethod = strtoupper($_POST['_method']);
    }

    // UPDATED: Dynamically create a dispatcher that loads all route files from the routes/ directory.
    $dispatcher = create_dispatcher(function () {
        $routeFiles = glob(ROUTES_PATH . '/*.php');
        if ($routeFiles === false) {
            write_log('Could not read routes directory.', 'critical');
            return;
        }
        foreach ($routeFiles as $file) {
            require_once $file;
        }
    });

    $routeInfo = $dispatcher->dispatch($httpMethod, $uri);
    $is_api_request = str_starts_with($uri, '/api');

    switch ($routeInfo[0]) {
        case Dispatcher::NOT_FOUND:
            if ($is_api_request) {
                echo json_response(['error' => 'Not Found'], 404);
            } else {
                status(404);
                echo "<h1>404 - Page Not Found</h1>"; // You could render a 404 view here
            }
            break;

        case Dispatcher::METHOD_NOT_ALLOWED:
            $allowedMethods = $routeInfo[1];
            if ($is_api_request) {
                echo json_response(['error' => 'Method Not Allowed'], 405, ['Allow' => implode(', ', $allowedMethods)]);
            } else {
                status(405);
                header('Allow: ' . implode(', ', $allowedMethods));
                echo "<h1>405 - Method Not Allowed</h1>";
            }
            break;

        case Dispatcher::FOUND:
            $handler_info = $routeInfo[1];
            $params = $routeInfo[2];
            try {
                $response = execute_pipeline($handler_info, $params);
                if (is_string($response) || is_numeric($response)) {
                    echo $response;
                } elseif (is_array($response) || is_object($response)) {
                    echo json_response($response);
                }
                // If $response is null, middleware likely handled the response and exited.
            } catch (Throwable $e) {
                write_log("Route execution error: " . $e->getMessage() . "\n" . $e->getTraceAsString(), 'error');
                if ($is_api_request) {
                    echo json_response(['error' => 'Internal Server Error'], 500);
                } else {
                    status(500);
                    echo "<h1>500 - Internal Server Error</h1>";
                    if (is_debug()) {
                        echo "<p><strong>Error:</strong> " . h($e->getMessage()) . "</p><pre>" . h($e->getTraceAsString()) . "</pre>";
                    }
                }
            }
            break;
    }
}