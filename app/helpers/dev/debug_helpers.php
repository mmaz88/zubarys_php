<?php
// app/helpers/dev/debug_helpers.php
declare(strict_types=1);

if (!function_exists('get_system_stats')) {
    /**
     * Gathers a comprehensive set of application and server statistics for debugging.
     * @return array<string, mixed>
     */
    function get_system_stats(): array
    {
        // --- Filesystem Helpers ---
        $getDirSize = function (string $path): int {
            $bytesTotal = 0;
            $path = realpath($path);
            if ($path !== false && file_exists($path)) {
                foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $object) {
                    $bytesTotal += $object->getSize();
                }
            }
            return $bytesTotal;
        };

        // --- OPcache Status ---
        $opcache = ['enabled' => false, 'message' => 'OPcache extension is not installed or enabled.'];
        if (function_exists('opcache_get_status') && ($status = opcache_get_status(false))) {
            if ($status['opcache_enabled']) {
                $opcache = [
                    'enabled' => true,
                    'PHP Files Cached' => number_format($status['opcache_statistics']['num_cached_scripts']),
                    'Memory Usage' => format_file_size($status['memory_usage']['used_memory']) . ' / ' . ini_get('opcache.memory_consumption'),
                    'Hit Rate' => round($status['opcache_statistics']['opcache_hit_rate'], 2) . '%',
                ];
            }
        }

        // --- Composer Dependencies ---
        $dependencies = [];
        $composerLockPath = ROOT_PATH . '/composer.lock';
        if (file_exists($composerLockPath)) {
            $lockFile = json_decode(file_get_contents($composerLockPath), true);
            $packages = $lockFile['packages'] ?? [];
            foreach ($packages as $package) {
                $dependencies[$package['name']] = $package['version'];
            }
            ksort($dependencies);
        }

        // --- Database Info ---
        $dbInfo = [];
        try {
            $pdo = db();
            $dbConfig = config('database.connections.' . config('database.default'));
            $dbInfo = [
                'Connection' => 'Successful',
                'Driver' => $pdo->getAttribute(PDO::ATTR_DRIVER_NAME),
                'Server Version' => $pdo->getAttribute(PDO::ATTR_SERVER_VERSION),
                'Host' => $dbConfig['host'] ?? 'N/A',
                'Database Name' => $dbConfig['database'] ?? 'N/A',
            ];
        } catch (Exception $e) {
            $dbInfo = ['Connection' => 'Failed: ' . $e->getMessage()];
        }

        $storage_free = disk_free_space(STORAGE_PATH);
        $root_free = disk_free_space(ROOT_PATH);

        // NOTE: The HTML here is coupled to specific CSS classes (e.g., text-success)
        // intended for a dedicated debug view.
        $debug_mode_html = is_debug()
            ? '<span class="text-success font-bold">Enabled</span>'
            : '<span>Disabled</span>';

        return [
            'environment' => [
                'PHP Version' => phpversion(),
                'Web Server' => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
                'Server OS' => php_uname('s') . ' ' . php_uname('r'),
                'App Environment' => h(env('APP_ENV', 'not set')),
                'Debug Mode' => $debug_mode_html,
                'App URL' => h(env('APP_URL', 'not set')),
            ],
            'php_config' => [
                'Memory Limit' => ini_get('memory_limit'),
                'Max Execution Time' => ini_get('max_execution_time') . 's',
                'Upload Max Filesize' => ini_get('upload_max_filesize'),
                'Post Max Size' => ini_get('post_max_size'),
                'Loaded Extensions' => get_loaded_extensions(),
            ],
            'database' => $dbInfo,
            'cache' => function_exists('cache_stats') ? cache_stats() : ['Error' => 'cache_stats() not found'],
            'filesystem' => [
                'Application Size' => format_file_size($getDirSize(ROOT_PATH)),
                'Storage Free Space' => $storage_free !== false ? format_file_size((int) $storage_free) : 'Unavailable',
                'Root Free Space' => $root_free !== false ? format_file_size((int) $root_free) : 'Unavailable',
            ],
            'opcache' => $opcache,
            'dependencies' => $dependencies,
        ];
    }
}
?>