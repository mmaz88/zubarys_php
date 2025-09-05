<?php
// app/api/system/DbCheckApi.php
declare(strict_types=1);

/**
 * Performs a database connection check and returns a JSON response.
 *
 * @return string
 */
function handle_db_check(): string
{
    try {
        // A lightweight way to verify the connection.
        db()->getAttribute(PDO::ATTR_SERVER_INFO);
        $status = 'ok';
        $message = 'Connection successful.';
        $http_status = 200;
    } catch (Exception $e) {
        $status = 'error';
        $message = 'Could not connect to the database.';
        $http_status = 503; // Service Unavailable

        if (is_debug()) {
            $message .= ' Error: ' . $e->getMessage();
        }
    }

    $response_data = [
        'status' => $status,
        'service' => 'database',
        'message' => $message
    ];

    if ($status === 'ok') {
        return success($response_data, 'Database is connected.', $http_status);
    } else {
        return error($message, $http_status, ['service' => 'database']);
    }
}