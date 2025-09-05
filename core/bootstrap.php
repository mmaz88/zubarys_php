<?php

/**
 * Core Bootstrap File
 *
 * Initializes the StarterKit, defines constants, and loads all helper functions with caching.
 */

declare(strict_types=1);

// Define StarterKit paths if they haven't been defined yet.
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}
if (!defined('APP_PATH')) {
    define('APP_PATH', ROOT_PATH . '/app');
}
if (!defined('API_PATH')) {
    define('API_PATH', APP_PATH . '/api');
}
if (!defined('ROUTES_PATH')) {
    define('ROUTES_PATH', ROOT_PATH . '/routes');
}
if (!defined('CORE_PATH')) {
    define('CORE_PATH', ROOT_PATH . '/core');
}
if (!defined('CONFIG_PATH')) {
    define('CONFIG_PATH', ROOT_PATH . '/config');
}
if (!defined('STORAGE_PATH')) {
    define('STORAGE_PATH', ROOT_PATH . '/storage');
}
if (!defined('CACHE_PATH')) {
    define('CACHE_PATH', STORAGE_PATH . '/cache');
}
if (!defined('LOGS_PATH')) {
    define('LOGS_PATH', STORAGE_PATH . '/logs');
}
if (!defined('PUBLIC_PATH')) {
    define('PUBLIC_PATH', ROOT_PATH . '/public');
}

if (!function_exists('load_env')) {
    /**
     * Load environment variables from .env file using the DotEnv package.
     */
    function load_env(): void
    {
        try {
            if (file_exists(ROOT_PATH . '/.env')) {
                $dotenv = Dotenv\Dotenv::createImmutable(ROOT_PATH);
                $dotenv->load();
            }
        } catch (Exception $e) {
            write_log("Could not load .env file: " . $e->getMessage(), 'critical');
        }
    }
}

if (!function_exists('env')) {
    /**
     * Get an environment variable or return a default value.
     */
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($value === false)
            return $default;
        return match (strtolower($value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'empty', '(empty)' => '',
            'null', '(null)' => null,
            default => $value,
        };
    }
}

// Load environment variables right away.
load_env();

// Configure error reporting based on environment settings.
error_reporting(E_ALL);
ini_set('display_errors', env('APP_DEBUG', false) ? '1' : '0');
ini_set('display_startup_errors', env('APP_DEBUG', false) ? '1' : '0');


if (!function_exists('init_cache')) {
    /**
     * Initialize the caching system by ensuring the cache directory exists.
     */
    function init_cache(): void
    {
        if (!is_dir(CACHE_PATH)) {
            mkdir(CACHE_PATH, 0755, true);
        }
    }
}

if (!function_exists('get_helper_files')) {
    /**
     * Returns the list of all core StarterKit and application helper files.
     *
     * IMPROVEMENT: This function now reads an explicit manifest file (`app/helpers/_autoload.php`).
     * This provides clear, maintainable, and explicit control over which helpers are loaded,
     * replacing the previous dynamic directory scanning logic.
     *
     * @return array<string>
     */
    function get_helper_files(): array
    {
        // Core StarterKit files that are always loaded
        $core_files = [
            CORE_PATH . '/kernel.php',
            CORE_PATH . '/router.php',
            CORE_PATH . '/request.php',
            CORE_PATH . '/response.php',
            CORE_PATH . '/db/DatabaseWrapper.php',
            CORE_PATH . '/session.php',
            CORE_PATH . '/services/mailer.php',
        ];

        // Load the application helper manifest
        $app_helpers_manifest = APP_PATH . '/helpers/_autoload.php';
        $app_helpers = [];

        if (file_exists($app_helpers_manifest)) {
            $helper_groups = require $app_helpers_manifest;
            // Flatten the array of groups into a single list of files
            foreach ($helper_groups as $group) {
                $app_helpers = array_merge($app_helpers, $group);
            }
        } else {
            write_log("Helper manifest not found: {$app_helpers_manifest}", 'warning');
        }

        return array_merge($core_files, $app_helpers);
    }
}


if (!function_exists('get_helpers_hash')) {
    /**
     * Get a hash of all helper files to validate the cache.
     */
    function get_helpers_hash(): string
    {
        $hash_data = '';
        foreach (get_helper_files() as $file) {
            if (file_exists($file)) {
                $hash_data .= filemtime($file) . filesize($file);
            }
        }
        return md5($hash_data);
    }
}

if (!function_exists('rebuild_helpers_cache')) {
    /**
     * Rebuild the single cached helper file from all individual helper files.
     */
    function rebuild_helpers_cache(string $cache_file, string $hash_file, string $hash): void
    {
        $cached_content = "<?php\n// Auto-generated helpers cache\n// Generated on: " . date('Y-m-d H:i:s') . "\n\n";

        foreach (get_helper_files() as $file) {
            if (file_exists($file)) {
                $content = file_get_contents($file);
                // Remove PHP opening tags and declare statements to prevent redeclaration errors.
                $content = preg_replace('/^<\?php\s*(declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;\s*)?/i', '', $content);
                $cached_content .= "\n// === " . basename($file) . " ===\n";
                $cached_content .= $content . "\n";
            }
        }
        file_put_contents($cache_file, $cached_content, LOCK_EX);
        file_put_contents($hash_file, $hash, LOCK_EX);
    }
}

if (!function_exists('load_helpers')) {
    /**
     * Load all helper functions, using a cached version for performance.
     */
    function load_helpers(): void
    {
        $cache_file = CACHE_PATH . '/_helpers.cache.php';
        $helpers_hash_file = CACHE_PATH . '/_helpers.hash';

        // In debug mode, skip caching to see changes immediately.
        if (env('APP_DEBUG', false)) {
            foreach (get_helper_files() as $file) {
                require_once $file;
            }
            return;
        }

        $current_hash = get_helpers_hash();

        if (file_exists($cache_file) && file_exists($helpers_hash_file)) {
            $cached_hash = file_get_contents($helpers_hash_file);
            if ($cached_hash === $current_hash) {
                require_once $cache_file;
                return;
            }
        }

        rebuild_helpers_cache($cache_file, $helpers_hash_file, $current_hash);
        require_once $cache_file;
    }
}


if (!function_exists('start_session')) {
    /**
     * Start session management.
     */
    function start_session(): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            $session_path = STORAGE_PATH . '/sessions';
            if (!is_dir($session_path)) {
                mkdir($session_path, 0755, true);
            }
            ini_set('session.save_path', $session_path);
            session_start();
        }
    }
}

if (!function_exists('config')) {
    /**
     * Load a configuration value using dot notation.
     */
    function config(string $key, mixed $default = null): mixed
    {
        static $config = [];
        $keys = explode('.', $key);
        $file = array_shift($keys);

        if (!isset($config[$file])) {
            $config_file = CONFIG_PATH . '/' . $file . '.php';
            if (file_exists($config_file)) {
                $config[$file] = require $config_file;
            } else {
                return $default;
            }
        }

        $value = $config[$file];
        foreach ($keys as $k) {
            if (is_array($value) && isset($value[$k])) {
                $value = $value[$k];
            } else {
                return $default;
            }
        }
        return $value;
    }
}

if (!function_exists('write_log')) {
    /**
     * A simple logging function.
     */
    function write_log(string|array|object $message, string $level = 'info'): void
    {
        if (!is_dir(LOGS_PATH))
            mkdir(LOGS_PATH, 0755, true);
        $log_file = LOGS_PATH . '/' . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        if (is_array($message) || is_object($message)) {
            $message = print_r($message, true);
        }
        $log_entry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
}

if (!function_exists('dd')) {
    /**
     * Dump and Die.
     */
    function dd(mixed ...$vars): void
    {
        echo '<pre>';
        foreach ($vars as $var)
            var_dump($var);
        echo '</pre>';
        exit;
    }
}

// Initialize StarterKit components
init_cache();
load_helpers();
start_session();