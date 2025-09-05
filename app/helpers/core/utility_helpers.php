<?php
/**
 * app/helpers/utility_helpers.php - Utility Functions
 *
 * This file contains various utility helper functions for string manipulation,
 * arrays, file sizes, and general application logic.
 */
declare(strict_types=1);

/**
 * Generate a URL-friendly "slug" from a given string.
 */
function str_slug(string $string, string $separator = '-'): string
{
    // Convert to lowercase
    $string = mb_strtolower($string, 'UTF-8');
    // Transliterate characters to ASCII
    $string = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
    // Remove characters that are not letters, numbers, spaces, or hyphens
    $string = preg_replace('/[^a-z0-9\s-]/', '', $string);
    // Replace spaces and multiple hyphens with a single separator
    $string = preg_replace('/[\s-]+/', $separator, trim($string));
    return $string;
}

/**
 * Limit the number of characters in a string.
 */
function str_limit(string $string, int $limit = 100, string $end = '...'): string
{
    if (mb_strlen($string) <= $limit) {
        return $string;
    }
    return rtrim(mb_substr($string, 0, $limit, 'UTF-8')) . $end;
}

/**
 * Format phone number to E.164 international format (e.g., +14155552671).
 */
function format_phone_number(string $phone): string
{
    // Remove all non-digit characters
    $phone = preg_replace('/\D/', '', $phone);
    // If it starts with a country code already, just ensure the '+' is there
    if (strlen($phone) > 10) {
        return '+' . ltrim($phone, '+');
    }
    // A simple assumption for US numbers if no country code is provided.
    if (strlen($phone) === 10) {
        return '+1' . $phone;
    }
    // For other cases, just prepend '+'
    return '+' . $phone;
}

/**
 * Format file size into a human-readable string.
 */
function format_file_size(int $bytes, int $precision = 2): string
{
    if ($bytes <= 0) {
        return '0 B';
    }
    $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
    $power = floor(log($bytes, 1024));
    return round($bytes / (1024 ** $power), $precision) . ' ' . $units[$power];
}

/**
 * Converts a datetime string to a human-readable "time ago" format.
 */
function time_ago(string $datetime): string
{
    try {
        $time = time() - (new DateTimeImmutable($datetime))->getTimestamp();
        if ($time < 1) {
            return 'just now';
        }
        $time_formats = [
            31536000 => 'year',
            2592000 => 'month',
            86400 => 'day',
            3600 => 'hour',
            60 => 'minute',
            1 => 'second'
        ];
        foreach ($time_formats as $seconds => $unit) {
            $division = $time / $seconds;
            if ($division >= 1) {
                $value = floor($division);
                return "{$value} {$unit}" . ($value > 1 ? 's' : '') . ' ago';
            }
        }
        return 'just now';
    } catch (Exception) {
        return $datetime;
    }
}

/**
 * Get an item from an array using "dot" notation.
 */
function array_get(array $array, ?string $key, mixed $default = null): mixed
{
    if ($key === null) {
        return $array;
    }
    if (isset($array[$key])) {
        return $array[$key];
    }
    foreach (explode('.', $key) as $segment) {
        if (!is_array($array) || !array_key_exists($segment, $array)) {
            return $default;
        }
        $array = $array[$segment];
    }
    return $array;
}

/**
 * Check if the application is in debug mode.
 */
function is_debug(): bool
{
    return (bool) config('app.debug', false);
}

/**
 * Generates a version 4 (random) UUID.
 */
function generate_uuidv4(): string
{
    $data = random_bytes(16);
    // Set version to 0100
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    // Set variant to 10xx
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/**
 * Generates a version 7 (time-ordered) UUID.
 *
 * IMPROVEMENT: The function has been rewritten to be compliant with the UUIDv7 RFC.
 * The previous implementation did not correctly set version and variant bits.
 *
 * @return string The UUIDv7 string.
 * @throws Exception
 */
function generate_uuidv7(): string
{
    $unix_ms = (int) floor(microtime(true) * 1000);
    $rand = random_bytes(10);

    // 48-bit timestamp
    $timestamp_hex = dechex($unix_ms);
    // Ensure it's 12 hex characters (48 bits)
    $timestamp_hex = str_pad($timestamp_hex, 12, '0', STR_PAD_LEFT);

    $bytes = hex2bin($timestamp_hex . bin2hex($rand));

    // Set version to 7 (0111)
    $bytes[6] = chr((ord($bytes[6]) & 0x0F) | 0x70);
    // Set variant to 10xx
    $bytes[8] = chr((ord($bytes[8]) & 0x3F) | 0x80);

    $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    return $uuid;
}