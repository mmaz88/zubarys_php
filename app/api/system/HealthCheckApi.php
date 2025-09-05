<?php
// app/api/system/HealthCheckApi.php
declare(strict_types=1);

/**
 * Performs a basic API health check and returns a JSON response.
 *
 * @return string
 */
function handle_health_check(): string
{
    return success([
        'status' => 'ok',
        'service' => 'api',
        'timestamp' => time()
    ], 'API is operational');
}