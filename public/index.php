<?php

/**
 * public/index.php - Main Entry Point
 *
 * This file is the single point of entry for all requests.
 */

declare(strict_types=1);

// 1. Load the Composer autoloader, which also loads our bootstrap file.
require_once __DIR__ . '/../vendor/autoload.php';

// 2. Pass control to the application kernel to handle the request.
handle_request();