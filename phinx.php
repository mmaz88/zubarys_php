<?php

// phinx.php

// Load the framework's bootstrap file to access helpers like config()
require_once __DIR__ . '/vendor/autoload.php';

// Get the default connection name and all connection details
$default_connection = config('database.default', 'mysql');
$db_config = config("database.connections.{$default_connection}");

if (!$db_config) {
    throw new \RuntimeException("Database configuration for '{$default_connection}' not found.");
}

// Map framework config to Phinx config structure
$driver_map = [
    'mysql' => 'mysql',
    'pgsql' => 'pgsql',
    'sqlite' => 'sqlite',
];

return
    [
        'paths' => [
            'migrations' => '%%PHINX_CONFIG_DIR%%/database/migrations',
            'seeds' => '%%PHINX_CONFIG_DIR%%/database/seeds'
        ],
        'environments' => [
            'default_migration_table' => 'phinxlog',
            'default_environment' => 'framework',
            'framework' => [
                'adapter' => $driver_map[$db_config['driver']] ?? 'mysql',
                'host' => $db_config['host'] ?? 'localhost',
                'name' => $db_config['database'],
                'user' => $db_config['username'] ?? 'root',
                'pass' => $db_config['password'] ?? '',
                'port' => $db_config['port'] ?? 3306,
                'charset' => $db_config['charset'] ?? 'utf8mb4',
                'collation' => $db_config['collation'] ?? 'utf8mb4_unicode_ci',
                'suffix' => '',
            ]
        ],
        'version_order' => 'creation'
    ];