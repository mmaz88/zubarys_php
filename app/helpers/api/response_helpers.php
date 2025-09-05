<?php // app/helpers/api/response_helpers.php

declare(strict_types=1);

/**
 * This file is responsible for generating complex API responses for COLLECTIONS of data.
 * Its primary function, `api_list_handler`, is the engine for powering data grids
 * and lists that require server-side pagination, sorting, and filtering.
 *
 * NOTE: The generic handler for fetching a SINGLE record (`api_get_handler`) has been
 * moved to `app/helpers/api/crud_helpers.php` to better group it with other
 * single-entity operations (create, update, delete).
 */
/**
 * Creates a standardized API response for paginated lists.
 *
 * @param array $config The configuration for the list endpoint.
 * @return string The JSON response.
 */
function api_list_handler(array $config): string
{
    if (input('draw') !== null) {
        try {
            $draw = (int) input('draw', 1);
            $start = (int) input('start', 0);
            $length = (int) input('length', 25);
            $searchValue = input('search')['value'] ?? '';
            $orderColumnIndex = input('order')[0]['column'] ?? 0;
            $orderDir = input('order')[0]['dir'] ?? 'desc';
            $columns = input('columns', []);

            $query = $config['base_query'];
            $recordsTotal = (clone $query)->count();

            $searchable = $config['searchable_columns'] ?? [];
            if (!empty($searchValue) && !empty($searchable)) {
                $query->where(function ($q) use ($searchValue, $searchable) {
                    foreach ($searchable as $column) {
                        $q->orWhere($column, 'LIKE', "%{$searchValue}%");
                    }
                });
            }
            $recordsFiltered = (clone $query)->count();

            $sortable = $config['sortable_columns'] ?? [];
            $orderColumnKey = $columns[$orderColumnIndex]['data'] ?? '';
            $is_sort_associative = !empty($sortable) && array_keys($sortable) !== range(0, count($sortable) - 1);
            $dbSortColumn = $is_sort_associative ? ($sortable[$orderColumnKey] ?? null) : (in_array($orderColumnKey, $sortable) ? $orderColumnKey : null);

            if ($dbSortColumn) {
                $query->orderBy($dbSortColumn, $orderDir);
            } elseif (!empty($config['default_sort'])) {
                $defaultSortKey = $config['default_sort']['column'];
                $dbSortColumn = $is_sort_associative ? ($sortable[$defaultSortKey] ?? $defaultSortKey) : $defaultSortKey;
                $query->orderBy($dbSortColumn, $config['default_sort']['direction']);
            }

            if ($length != -1) {
                $query->limit($length)->offset($start);
            }

            $data = $query->get();

            $response = [
                'draw' => $draw,
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data' => $data,
            ];

            header_set('Content-Type', 'application/json');
            return json_encode($response);
        } catch (Throwable $e) {
            write_log("API List Handler (DataTables) Error: " . $e->getMessage(), 'critical');
            return json_encode([
                'draw' => (int) input('draw', 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => is_debug() ? $e->getMessage() : 'An internal server error occurred.'
            ]);
        }
    } else {
        try {
            $page = (int) (input('page', 1));
            $perPage = (int) (input('per_page', 25));
            $sortDirInput = strtolower(input('sort_dir', $config['default_sort']['direction']));
            $sortDir = in_array($sortDirInput, ['asc', 'desc']) ? $sortDirInput : $config['default_sort']['direction'];
            $query = $config['base_query'];

            $sortable = $config['sortable_columns'] ?? [];
            $is_sort_associative = !empty($sortable) && array_keys($sortable) !== range(0, count($sortable) - 1);
            $sortByInput = input('sort_by', $config['default_sort']['column']);
            $dbSortColumn = null;
            if ($is_sort_associative) {
                if (isset($sortable[$sortByInput])) {
                    $dbSortColumn = $sortable[$sortByInput];
                }
            } else {
                if (in_array($sortByInput, $sortable)) {
                    $dbSortColumn = $sortByInput;
                }
            }
            if (!$dbSortColumn) {
                $defaultSortKey = $config['default_sort']['column'];
                $dbSortColumn = $is_sort_associative ? ($sortable[$defaultSortKey] ?? $defaultSortKey) : $defaultSortKey;
            }
            if ($dbSortColumn && $sortDir) {
                $query->orderBy($dbSortColumn, $sortDir);
            }

            $searchParam = input('search', '');
            $searchable = $config['searchable_columns'] ?? [];
            $is_search_associative = !empty($searchable) && array_keys($searchable) !== range(0, count($searchable) - 1);
            $structuredFilters = json_decode($searchParam, true);
            if (is_array($structuredFilters) && json_last_error() === JSON_ERROR_NONE) {
                foreach ($structuredFilters as $columnKey => $value) {
                    if (!empty($value)) {
                        $dbSearchColumn = $is_search_associative ? ($searchable[$columnKey] ?? null) : (in_array($columnKey, $searchable) ? $columnKey : null);
                        if ($dbSearchColumn) {
                            $query->where($dbSearchColumn, 'LIKE', "%{$value}%");
                        }
                    }
                }
            } elseif (!empty($searchParam)) {
                $columnsToSearch = $is_search_associative ? array_values($searchable) : $searchable;
                if (!empty($columnsToSearch)) {
                    $query->where(function ($q) use ($searchParam, $columnsToSearch) {
                        foreach ($columnsToSearch as $column) {
                            $q->orWhere($column, 'LIKE', "%{$searchParam}%");
                        }
                    });
                }
            }

            $paginatedData = $query->paginate($perPage, $page);
            $responseData = [
                'data' => $paginatedData['data'],
                'pagination' => $paginatedData['pagination']
            ];
            return success($responseData, 'Data fetched successfully.');
        } catch (Throwable $e) {
            write_log("API List Handler Error: " . $e->getMessage(), 'critical');
            if (env('APP_ENV') === 'local' || is_debug()) {
                $debug_info = [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => explode("\n", $e->getTraceAsString()),
                ];
                return error('An internal server error occurred. See debug info.', 500, ['debug' => $debug_info]);
            }
            return error('An internal server error occurred.', 500);
        }
    }
}