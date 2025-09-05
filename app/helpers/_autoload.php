<?php

/**
 * Helper Autoload Manifest
 *
 * This file returns an array of all helper files that should be loaded by the bootstrap process.
 * To activate a new helper file, add its path to this array.
 *
 * The paths should be relative to the application root using the ROOT_PATH constant.
 */

return [
    // Core Application Helpers
    'core' => [
        APP_PATH . '/helpers/core/security_helpers.php',
        APP_PATH . '/helpers/core/utility_helpers.php',
        APP_PATH . '/helpers/core/validation_helpers.php',
    ],

    // API Helpers
    'api' => [
        APP_PATH . '/helpers/api/crud_helpers.php',
        APP_PATH . '/helpers/api/response_helpers.php',
    ],

    // Development & Debugging Helpers
    'dev' => [
        APP_PATH . '/helpers/dev/debug_helpers.php',
    ],

    // Integration Helpers (e.g., third-party libraries)
    'integrations' => [
        APP_PATH . '/helpers/integrations/phpoffice_helpers.php',
    ],
    'middleware' => [
        APP_PATH . '/middleware/PermissionMiddleware.php',
    ],

    // View & Presentation Helpers
    'view' => [
        APP_PATH . '/helpers/view/asset_helpers.php',
        APP_PATH . '/helpers/view/component_helpers.php',
        APP_PATH . '/helpers/view/form_helpers.php',
        APP_PATH . '/helpers/view/layout_helpers.php',
        APP_PATH . '/helpers/view/datatables_helpers.php',
    ],
];