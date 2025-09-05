<?php
/**
 * File-based Caching System
 */

/**
 * Store data in cache
 */
function cache_put($key, $value, $ttl = 3600)
{
    $cache_file = get_cache_file($key);
    $cache_dir = dirname($cache_file);

    if (!file_exists($cache_dir)) {
        mkdir($cache_dir, 0755, true);
    }

    $cache_data = [
        'value' => $value,
        'expires_at' => time() + $ttl,
        'created_at' => time()
    ];

    $serialized = serialize($cache_data);

    return file_put_contents($cache_file, $serialized, LOCK_EX) !== false;
}

/**
 * Get data from cache
 */
function cache_get($key, $default = null)
{
    $cache_file = get_cache_file($key);

    if (!file_exists($cache_file)) {
        return $default;
    }

    $content = file_get_contents($cache_file);

    if ($content === false) {
        return $default;
    }

    $cache_data = unserialize($content);

    if (!is_array($cache_data) || !isset($cache_data['expires_at'])) {
        cache_forget($key);
        return $default;
    }

    // Check if cache has expired
    if (time() > $cache_data['expires_at']) {
        cache_forget($key);
        return $default;
    }

    return $cache_data['value'];
}

/**
 * Check if cache key exists and is not expired
 */
function cache_has($key)
{
    return cache_get($key) !== null;
}

/**
 * Remove item from cache
 */
function cache_forget($key)
{
    $cache_file = get_cache_file($key);

    if (file_exists($cache_file)) {
        return unlink($cache_file);
    }

    return true;
}

/**
 * Clear all cache
 */
function cache_flush()
{
    $cache_path = CACHE_PATH;

    if (!file_exists($cache_path)) {
        return true;
    }

    $files = glob($cache_path . '/*');

    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }

    return true;
}

/**
 * Get or set cache value
 */
function cache($key, $value = null, $ttl = 3600)
{
    if ($value === null) {
        return cache_get($key);
    }

    cache_put($key, $value, $ttl);
    return $value;
}

/**
 * Remember cache value with callback
 */
function cache_remember($key, $ttl, $callback)
{
    $value = cache_get($key);

    if ($value !== null) {
        return $value;
    }

    $value = call_user_func($callback);

    cache_put($key, $value, $ttl);

    return $value;
}

/**
 * Remember cache value forever
 */
function cache_forever($key, $callback)
{
    return cache_remember($key, 315360000, $callback); // ~10 years
}

/**
 * Increment cache value
 */
function cache_increment($key, $value = 1)
{
    $current = cache_get($key, 0);

    if (!is_numeric($current)) {
        $current = 0;
    }

    $new_value = $current + $value;
    cache_put($key, $new_value);

    return $new_value;
}

/**
 * Decrement cache value
 */
function cache_decrement($key, $value = 1)
{
    return cache_increment($key, -$value);
}

/**
 * Get cache file path for key
 */
function get_cache_file($key)
{
    $hash = md5($key);
    $sub_dir = substr($hash, 0, 2);

    return CACHE_PATH . '/' . $sub_dir . '/' . $hash . '.cache';
}

/**
 * Clean expired cache files
 */
function cache_clean()
{
    $cache_path = CACHE_PATH;

    if (!file_exists($cache_path)) {
        return 0;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($cache_path)
    );

    $cleaned = 0;

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'cache') {
            $content = file_get_contents($file->getPathname());

            if ($content !== false) {
                $cache_data = unserialize($content);

                if (is_array($cache_data) && isset($cache_data['expires_at'])) {
                    if (time() > $cache_data['expires_at']) {
                        unlink($file->getPathname());
                        $cleaned++;
                    }
                }
            }
        }
    }

    return $cleaned;
}

/**
 * Get cache statistics
 */
function cache_stats()
{
    $cache_path = CACHE_PATH;

    if (!file_exists($cache_path)) {
        return [
            'total_files' => 0,
            'total_size' => 0,
            'expired_files' => 0
        ];
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($cache_path)
    );

    $total_files = 0;
    $total_size = 0;
    $expired_files = 0;

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'cache') {
            $total_files++;
            $total_size += $file->getSize();

            $content = file_get_contents($file->getPathname());

            if ($content !== false) {
                $cache_data = unserialize($content);

                if (is_array($cache_data) && isset($cache_data['expires_at'])) {
                    if (time() > $cache_data['expires_at']) {
                        $expired_files++;
                    }
                }
            }
        }
    }

    return [
        'total_files' => $total_files,
        'total_size' => $total_size,
        'total_size_formatted' => format_bytes($total_size),
        'expired_files' => $expired_files
    ];
}

/**
 * Format bytes to human readable size
 */
function format_bytes($bytes, $precision = 2)
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];

    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }

    return round($bytes, $precision) . ' ' . $units[$i];
}