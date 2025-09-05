<?php
// app/helpers/api/crud_helpers.php
declare(strict_types=1);

/**
 * Generic API handler for creating records.
 *
 * IMPROVEMENT: All database operations are now wrapped in a transaction to ensure data integrity.
 *
 * @param array $config Configuration array.
 * @return string JSON response
 */
function api_create_handler(array $config): string
{
    db()->beginTransaction(); // <-- START TRANSACTION
    try {
        if (empty($config['table']) || empty($config['validation_rules']) || empty($config['fillable_fields'])) {
            throw new InvalidArgumentException('Missing required config: table, validation_rules, or fillable_fields');
        }

        $data = input();
        $errors = validate($data, $config['validation_rules']);
        if (!empty($errors)) {
            return validation_error($errors);
        }

        $insertData = [];
        foreach ($config['fillable_fields'] as $field) {
            if (array_key_exists($field, $data)) {
                $insertData[$field] = sanitize($data[$field]);
            }
        }

        if (!empty($config['auto_fields'])) {
            foreach ($config['auto_fields'] as $field => $generator) {
                $insertData[$field] = is_callable($generator) ? $generator() : $generator;
            }
        }

        if (isset($config['transform_data']) && is_callable($config['transform_data'])) {
            $insertData = $config['transform_data']($insertData, $data);
        }

        $newId = table($config['table'])->insert($insertData);
        $newRecord = table($config['table'])->where('id', '=', $newId)->first();

        if (isset($config['after_create']) && is_callable($config['after_create'])) {
            $config['after_create']($newRecord, $data);
        }

        db()->commit(); // <-- COMMIT TRANSACTION

        $message = $config['success_message'] ?? ucfirst(str_replace('_', ' ', $config['table'])) . ' created successfully.';
        return success($newRecord, $message, 201);

    } catch (Throwable $e) {
        db()->rollBack(); // <-- ROLLBACK ON ERROR
        $tableName = $config['table'] ?? 'unknown';
        write_log("Generic Create API Error for table {$tableName}: " . $e->getMessage(), 'critical');
        return error('An internal server error occurred.', 500);
    }
}

/**
 * MOVED: Generic API handler for fetching a single record (the "Read" in CRUD).
 *
 * WHY IT'S HERE: This function logically belongs with the other single-entity handlers
 * (create, update, delete) to complete the full set of CRUD operations in one file.
 *
 * @param array $config Configuration array:
 *  - 'table' (string): The database table name.
 *  - 'id' (string|int): The ID of the record to fetch.
 *  - 'id_column' (string, optional): The name of the primary key column (defaults to 'id').
 *  - 'security_check' (callable, optional): A function that receives the record and returns bool.
 *  - 'fields_to_unset' (array, optional): Array of field names to remove before sending the response.
 * @return string The JSON response.
 */
function api_get_handler(array $config): string
{
    try {
        if (empty($config['table']) || empty($config['id'])) {
            throw new InvalidArgumentException('Missing required config: table or id');
        }

        $idColumn = $config['id_column'] ?? 'id';
        $record = table($config['table'])->where($idColumn, '=', $config['id'])->first();

        if (!$record) {
            return error(ucfirst(str_replace('_', ' ', $config['table'])) . ' not found.', 404);
        }

        if (isset($config['security_check']) && is_callable($config['security_check'])) {
            if (!$config['security_check']($record)) {
                return error('Forbidden.', 403);
            }
        }

        if (!empty($config['fields_to_unset'])) {
            foreach ($config['fields_to_unset'] as $field) {
                unset($record[$field]);
            }
        }

        return success($record);
    } catch (Throwable $e) {
        $tableName = $config['table'] ?? 'unknown';
        write_log("API Get Handler Error for table {$tableName}: " . $e->getMessage(), 'critical');
        if (is_debug()) {
            $debug_info = [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ];
            return error('An internal server error occurred.', 500, ['debug' => $debug_info]);
        }
        return error('An internal server error occurred.', 500);
    }
}

/**
 * Generic API handler for updating records.
 *
 * IMPROVEMENT: All database operations are now wrapped in a transaction.
 *
 * @param array $config Configuration array.
 * @return string JSON response
 */
function api_update_handler(array $config): string
{
    db()->beginTransaction(); // <-- START TRANSACTION
    try {
        if (empty($config['table']) || empty($config['id']) || empty($config['validation_rules']) || empty($config['fillable_fields'])) {
            throw new InvalidArgumentException('Missing required config: table, id, validation_rules, or fillable_fields');
        }

        $data = input();
        $idColumn = $config['id_column'] ?? 'id';
        $existingRecord = table($config['table'])->where($idColumn, '=', $config['id'])->first();
        if (!$existingRecord) {
            db()->rollBack();
            return error(ucfirst(str_replace('_', ' ', $config['table'])) . " not found.", 404);
        }

        $errors = validate($data, $config['validation_rules']);
        if (!empty($errors)) {
            return validation_error($errors);
        }

        if (isset($config['before_update']) && is_callable($config['before_update'])) {
            $beforeResult = $config['before_update']($data, $existingRecord);
            if ($beforeResult !== true) {
                return is_string($beforeResult) ? error($beforeResult, 400) : error('Update validation failed.', 400);
            }
        }

        $updateData = [];
        foreach ($config['fillable_fields'] as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = sanitize($data[$field]);
            }
        }

        if (!empty($config['auto_fields'])) {
            foreach ($config['auto_fields'] as $field => $generator) {
                $updateData[$field] = is_callable($generator) ? $generator() : $generator;
            }
        }

        if (isset($config['transform_data']) && is_callable($config['transform_data'])) {
            $updateData = $config['transform_data']($updateData, $data, $existingRecord);
        }

        table($config['table'])->where($idColumn, '=', $config['id'])->update($updateData);
        $updatedRecord = table($config['table'])->where($idColumn, '=', $config['id'])->first();

        if (isset($config['after_update']) && is_callable($config['after_update'])) {
            $config['after_update']($updatedRecord, $existingRecord, $data);
        }

        db()->commit(); // <-- COMMIT TRANSACTION

        $message = $config['success_message'] ?? ucfirst(str_replace('_', ' ', $config['table'])) . ' updated successfully.';
        return success($updatedRecord, $message);
    } catch (Throwable $e) {
        db()->rollBack(); // <-- ROLLBACK ON ERROR
        $tableName = $config['table'] ?? 'unknown';
        write_log("Generic Update API Error for table {$tableName}: " . $e->getMessage(), 'critical');
        return error('An internal server error occurred.', 500);
    }
}

/**
 * Generic API handler for deleting records.
 *
 * IMPROVEMENT: All database operations are now wrapped in a transaction.
 *
 * @param array $config Configuration array.
 * @return string JSON response
 */
function api_delete_handler(array $config): string
{
    db()->beginTransaction(); // <-- START TRANSACTION
    try {
        if (empty($config['table']) || empty($config['id'])) {
            throw new InvalidArgumentException('Missing required config: table or id');
        }

        $idColumn = $config['id_column'] ?? 'id';
        $existingRecord = table($config['table'])->where($idColumn, '=', $config['id'])->first();
        if (!$existingRecord) {
            db()->rollBack();
            return error(ucfirst(str_replace('_', ' ', $config['table'])) . " not found.", 404);
        }

        if (isset($config['before_delete']) && is_callable($config['before_delete'])) {
            $beforeResult = $config['before_delete']($existingRecord);
            if ($beforeResult !== true) {
                return is_string($beforeResult) ? error($beforeResult, 403) : error('Delete operation not allowed.', 403);
            }
        }

        $deletedRows = 0;
        if (!empty($config['soft_delete'])) {
            $softDeleteColumn = $config['soft_delete_column'] ?? 'deleted_at';
            $updateData = [$softDeleteColumn => date('Y-m-d H:i:s')];
            $deletedRows = table($config['table'])->where($idColumn, '=', $config['id'])->update($updateData);
        } else {
            $deletedRows = table($config['table'])->where($idColumn, '=', $config['id'])->delete();
        }

        if ($deletedRows > 0) {
            if (isset($config['after_delete']) && is_callable($config['after_delete'])) {
                $config['after_delete']($existingRecord);
            }

            db()->commit(); // <-- COMMIT TRANSACTION

            $message = $config['success_message'] ?? ucfirst(str_replace('_', ' ', $config['table'])) . ' deleted successfully.';
            return success(null, $message);
        }

        throw new Exception('Failed to delete record, 0 rows affected.');

    } catch (Throwable $e) {
        db()->rollBack(); // <-- ROLLBACK ON ERROR
        $tableName = $config['table'] ?? 'unknown';
        write_log("Generic Delete API Error for table {$tableName}: " . $e->getMessage(), 'critical');
        return error('An internal server error occurred.', 500);
    }
}


/**
 * Example usage configurations for different scenarios:
 */
// ... (All original examples remain here, they are still valid) ...

/**
 * Utility function to create standard auto-fields for timestamps and user tracking.
 */
function standard_auto_fields(bool $isUpdate = false): array
{
    $fields = [];
    if (!$isUpdate) {
        $fields['id'] = fn() => generate_uuidv7();
        $fields['created_at'] = fn() => date('Y-m-d H:i:s');
        if (function_exists('session') && session('user_id')) {
            $fields['created_by'] = fn() => session('user_id');
        }
    }
    $fields['updated_at'] = fn() => date('Y-m-d H:i:s');
    if (function_exists('session') && session('user_id')) {
        $fields['updated_by'] = fn() => session('user_id');
    }
    return $fields;
}

/**
 * Utility function to create standard validation rules with unique checks.
 */
function standard_validation_rules(string $table, ?string $excludeId = null): array
{
    $rules = [
        'name' => 'required|min:3|max:100',
        'description' => 'max:1000',
        'is_active' => 'boolean',
    ];
    // Add unique validation that excludes current record for updates
    if ($excludeId) {
        $rules['name'] .= "|unique:{$table},name,{$excludeId}";
    } else {
        $rules['name'] .= "|unique:{$table},name";
    }
    return $rules;
}