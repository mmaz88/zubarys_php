<?php
/**
 * app/helpers/validation_helpers.php - Validation Functions
 *
 * This file contains functions for validating input data against a set of rules.
 */
declare(strict_types=1);

/**
 * Validate input data against a set of rules.
 */
function validate(array $data, array $rules, array $messages = []): array
{
    $errors = [];
    foreach ($rules as $field => $rule_string) {
        $rules_array = explode('|', $rule_string);
        $value = $data[$field] ?? null;
        foreach ($rules_array as $rule) {
            $rule_parts = explode(':', $rule, 2);
            $rule_name = $rule_parts[0];
            $rule_param = $rule_parts[1] ?? null;
            $error = validate_field($field, $value, $rule_name, $rule_param, $data);
            if ($error) {
                $message_key = "{$field}.{$rule_name}";
                $errors[$field] = $messages[$message_key] ?? $error;
                break; // Stop at first error for this field
            }
        }
    }
    return $errors;
}

/**
 * Validate a single field against a specific rule.
 */
function validate_field(string $field, mixed $value, string $rule, ?string $param = null, array $all_data = []): ?string
{
    // Handle 'required' separately to ensure empty values are checked correctly.
    if ($rule === 'required' && (empty($value) && $value !== '0' && $value !== 0)) {
        return "The {$field} field is required.";
    }
    // Skip validation for other rules if the value is empty. Use 'required' to enforce presence.
    if (empty($value) && $value !== '0' && $value !== 0) {
        return null;
    }
    return match ($rule) {
        'email' => !filter_var($value, FILTER_VALIDATE_EMAIL) ? "The {$field} must be a valid email address." : null,
        'min' => strlen((string) $value) < (int) $param ? "The {$field} must be at least {$param} characters." : null,
        'max' => strlen((string) $value) > (int) $param ? "The {$field} may not be greater than {$param} characters." : null,
        'numeric' => !is_numeric($value) ? "The {$field} must be a number." : null,
        'integer' => filter_var($value, FILTER_VALIDATE_INT) === false ? "The {$field} must be an integer." : null,
        'url' => !filter_var($value, FILTER_VALIDATE_URL) ? "The {$field} must be a valid URL." : null,
        'confirmed' => $value !== ($all_data[$field . '_confirmation'] ?? null) ? "The {$field} confirmation does not match." : null,
        'unique' => is_unique($value, $param, $field),
        'exists' => record_exists($value, $param, $field),
        default => null,
    };
}

/**
 * Helper for the 'unique' validation rule.
 *
 * IMPROVEMENT: Now supports excluding a specific ID, which is essential for update operations.
 * The format is `unique:table,column,id_to_exclude,id_column_name`.
 *
 * @param mixed $value The value to check.
 * @param string|null $param Parameters in "table,column,exclude_id,id_column" format.
 * @param string $field The field name being validated.
 * @return string|null Error message or null.
 */
function is_unique(mixed $value, ?string $param, string $field): ?string
{
    if (!$param) {
        return "Validation rule 'unique' requires parameters (e.g., table,column).";
    }

    $parts = array_map('trim', explode(',', $param));
    $table = $parts[0];
    $column = $parts[1] ?? $field;
    $excludeId = $parts[2] ?? null;
    $idColumn = $parts[3] ?? 'id';

    $query = table($table)->where($column, '=', $value);

    if ($excludeId) {
        $query->where($idColumn, '!=', $excludeId);
    }

    if ($query->first()) {
        return "The {$field} has already been taken.";
    }

    return null;
}

/**
 * Helper for the 'exists' validation rule.
 */
function record_exists(mixed $value, ?string $param, string $field): ?string
{
    if (!$param) {
        return "Validation rule 'exists' requires parameters (table,column).";
    }
    [$table, $column] = array_map('trim', explode(',', $param, 2));
    $column = $column ?: $field;
    if (!table($table)->where($column, '=', $value)->first()) {
        return "The selected {$field} is invalid.";
    }
    return null;
}

/**
 * Sanitize an input string for safe use.
 */
function sanitize(?string $input): string
{
    return htmlspecialchars(trim((string) $input), ENT_QUOTES, 'UTF-8');
}

/**
 * DUPLICATE/REMOVED: The `function_calls` helper was a redundant wrapper
 * for PHP's native `function_exists()`. It has been removed to avoid confusion.
 * Direct usage of `function_exists()` is recommended.
 */