<?php

/**
 * app/middleware/CorsMiddleware.php
 *
 * Handles Cross-Origin Resource Sharing (CORS) for API requests.
 */

declare(strict_types=1);

// Allow requests from any origin. For production, you should restrict this.
// e.g., header_set('Access-Control-Allow-Origin', config('app.url'));
header_set('Access-Control-Allow-Origin', '*');
header_set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
header_set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
header_set('Access-Control-Max-Age', '86400'); // Cache preflight requests for 1 day

// Handle preflight OPTIONS requests
if (get_request_method() === 'OPTIONS') {
    status(204); // No Content
    exit;
}

return true;