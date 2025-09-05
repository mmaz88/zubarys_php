<?php // routes/system_routes.php


// The system health check routes can remain.
group(['prefix' => 'api', 'middleware' => ['CorsMiddleware']], function () {
    // --- System Health Checks ---
    group(['prefix' => 'system'], function () {
        // General API health check
        get('/health', function () {
            require_once API_PATH . '/system/HealthCheckApi.php';
            return handle_health_check();
        });
        // Database connection check
        get('/db', function () {
            require_once API_PATH . '/system/DbCheckApi.php';
            return handle_db_check();
        });
    });
});