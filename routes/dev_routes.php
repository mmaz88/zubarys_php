<?php
// FILE: routes/dev.php
declare(strict_types=1);

/**
 * Developer-only routes.
 * These routes are only registered when the APP_ENV is set to 'local'.
 */
if (env('APP_ENV') === 'local') {
    group(['prefix' => 'dev'], function () {

        // The main page for developer system stats
        get('/system', function () {
            return view(
                'dev.system_info', // Renders the view from app/views/dev/system_info.php
                [
                    'title' => 'System Info',
                    'page_title' => 'Developer System Info',
                    'stats' => get_system_stats(), // Get data from our new helper
                    'breadcrumbs' => [
                        ['text' => 'Home', 'url' => '/dashboard'],
                        ['text' => 'Developer'],
                        ['text' => 'System Info'],
                    ],
                ],
                'layout.main' // Use the main application layout
            );
        });
    });
}