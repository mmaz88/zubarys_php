<?php // app/helpers/view/asset_helpers.php

declare(strict_types=1);

/**
 * Generates a <link> tag for a CSS file with cache-busting timestamp.
 */
function css(string $file, array $attributes = []): string
{
    $url = asset($file, 'assets/css', 'css');
    $attrs = array_merge([
        'rel' => 'stylesheet',
        'type' => 'text/css',
        'href' => $url
    ], $attributes);
    return '<link ' . build_attributes($attrs) . '>';
}

/**
 * Generates multiple CSS <link> tags.
 */
function css_files(array $files, array $attributes = []): string
{
    $output = array_map(fn($file) => css($file, $attributes), $files);
    return implode("\n", $output);
}

/**
 * Generate a script tag for a JavaScript file.
 * NOTE: This function remains unchanged as requested.
 */
function js(string $file, bool $addTimestamp = true, array $attributes = []): string
{
    // The asset() function below will handle the global timestamp logic.
    // The $addTimestamp flag here can be used for per-file overrides if needed.
    $url = asset($file, 'assets/js', 'js', $addTimestamp);
    $attrs = array_merge([
        'type' => 'text/javascript',
        'src' => $url
    ], $attributes);
    return '<script ' . build_attributes($attrs) . '></script>';
}

/**
 * Generates multiple JavaScript <script> tags.
 */
function js_files(array $files, bool $addTimestamp = true, array $attributes = []): string
{
    $output = array_map(fn($file) => js($file, $addTimestamp, $attributes), $files);
    return implode("\n", $output);
}

/**
 * Generates an <img> tag with a cache-busting timestamp.
 */
function img(string $file, string $alt = '', array $attributes = []): string
{
    $url = asset($file, 'assets/images');
    $attrs = array_merge([
        'src' => $url,
        'alt' => h($alt)
    ], $attributes);
    return '<img ' . build_attributes($attrs) . '>';
}

/**
 * Generates a URL for an asset file with an optional cache-busting timestamp.
 */
function asset(string $file, ?string $defaultDir = null, ?string $extension = null, bool $addTimestamp = true): string
{
    $file = ltrim($file, '/');
    if (preg_match('/^https?:\/\//', $file)) {
        return $file;
    }

    $base_url = get_base_url();
    if ($defaultDir && !str_starts_with($file, 'assets/')) {
        $file = $defaultDir . '/' . $file;
    }
    if ($extension && !str_ends_with($file, '.' . $extension)) {
        $file .= '.' . $extension;
    }

    $url = $base_url . '/' . $file;

    if ($addTimestamp && should_add_timestamp()) {
        $file_path = PUBLIC_PATH . '/' . $file;
        if (file_exists($file_path)) {
            $timestamp = filemtime($file_path);
            $url .= '?v=' . $timestamp;
        }
    }

    return $url;
}


/**
 * Gets the base URL of the application.
 */
function get_base_url(): string
{
    static $base_url = null;
    if ($base_url === null) {
        $base_url = rtrim(env('APP_URL', ''), '/');
        if (empty($base_url)) {
            $protocol = is_secure() ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $base_url = $protocol . '://' . $host;
        }
    }
    return $base_url;
}

/**
 * Determines if a cache-busting timestamp should be added to asset URLs.
 *
 * IMPROVEMENT: The logic has been corrected. Cache busting is most important in production
 * to ensure users get new assets after a deployment. It is now enabled when the
 * environment is 'production' or when debug mode is on.
 *
 * @return bool
 */
function should_add_timestamp(): bool
{
    $env = env('APP_ENV', 'production');
    $debug = (bool) env('APP_DEBUG', false);

    // Add timestamp in production OR if debug mode is explicitly enabled.
    return $env === 'production' || $debug === true;
}