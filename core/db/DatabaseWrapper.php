<?php

/**
 * core/db/DatabaseWrapper.php - Comprehensive Database Handler & Query Builder
 *
 * Provides a PDO wrapper, a powerful Query Builder, and handles dialect differences
 * for MySQL, PostgreSQL, and SQLite.
 *
 * @version 2.0
 */

declare(strict_types=1);

// --- Quoter System for SQL Dialects ---

/**
 * Defines the contract for a database identifier quoter.
 */
interface Quoter
{
    /**
     * Quotes a table or column name.
     * @param string $identifier The identifier to quote.
     * @return string The quoted identifier.
     */
    public function quote(string $identifier): string;
}

/**
 * Handles quoting for MySQL and SQLite (backticks).
 */
class MySqlQuoter implements Quoter
{
    public function quote(string $identifier): string
    {
        // Handle alias "table as alias"
        if (stripos($identifier, ' as ') !== false) {
            $parts = preg_split('/ as /i', $identifier);
            return $this->quote(trim($parts[0])) . ' as ' . $this->quote(trim($parts[1]));
        }
        // Handle dot notation "table.column"
        if (strpos($identifier, '.') !== false) {
            return implode('.', array_map([$this, 'quote'], explode('.', $identifier)));
        }
        return '`' . str_replace('`', '``', $identifier) . '`';
    }
}

/**
 * Handles quoting for PostgreSQL (double quotes).
 */
class PostgresQuoter implements Quoter
{
    public function quote(string $identifier): string
    {
        // Handle alias "table as alias"
        if (stripos($identifier, ' as ') !== false) {
            $parts = preg_split('/ as /i', $identifier);
            return $this->quote(trim($parts[0])) . ' as ' . $this->quote(trim($parts[1]));
        }
        // Handle dot notation "table.column"
        if (strpos($identifier, '.') !== false) {
            return implode('.', array_map([$this, 'quote'], explode('.', $identifier)));
        }
        return '"' . str_replace('"', '""', $identifier) . '"';
    }
}

/**
 * A fallback quoter for unknown drivers.
 */
class PassthroughQuoter implements Quoter
{
    public function quote(string $identifier): string
    {
        return $identifier;
    }
}


// --- Core Database Functions ---

$GLOBALS['db_connections'] = [];
$GLOBALS['db_quoters'] = [];

/**
 * Get a database connection instance (PDO).
 *
 * @param string|null $connection The name of the connection from the config file.
 * @return PDO The PDO instance.
 * @throws Exception if the connection is not configured.
 */
function db(?string $connection = null): PDO
{
    // Fallback to a default if config() is not available or not set
    $default_connection = function_exists('config') ? config('database.default', 'mysql') : 'mysql';
    $connection_name = $connection ?? $default_connection;

    if (!isset($GLOBALS['db_connections'][$connection_name])) {
        $GLOBALS['db_connections'][$connection_name] = create_connection($connection_name);
    }
    return $GLOBALS['db_connections'][$connection_name];
}

/**
 * Creates a new database connection and initializes its quoter.
 *
 * @param string $connection_name The name of the connection.
 * @return PDO The new PDO instance.
 * @throws Exception if configuration is missing or connection fails.
 */
function create_connection(string $connection_name): PDO
{
    if (!function_exists('config')) {
        throw new Exception("Configuration function 'config()' not found.");
    }
    $config = config("database.connections.{$connection_name}");
    if (!$config) {
        throw new Exception("Database connection '{$connection_name}' not configured.");
    }

    try {
        $dsn = build_dsn($config);
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $pdo = new PDO($dsn, $config['username'] ?? '', $config['password'] ?? '', $options);

        $driver = $config['driver'] ?? 'mysql';
        $GLOBALS['db_quoters'][$connection_name] = match ($driver) {
            'mysql', 'sqlite' => new MySqlQuoter(),
            'pgsql' => new PostgresQuoter(),
            default => new PassthroughQuoter(),
        };

        if ($driver === 'mysql') {
            // Set ONLY_FULL_GROUP_BY among other strict modes for MySQL
            $pdo->exec("SET SESSION sql_mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
            $pdo->exec("SET NAMES " . ($config['charset'] ?? 'utf8mb4'));
        }

        return $pdo;
    } catch (PDOException $e) {
        // In a real app, use a proper logging mechanism
        error_log("Database connection failed: " . $e->getMessage());
        throw new Exception("Database connection failed: " . $e->getMessage(), (int) $e->getCode(), $e);
    }
}

/**
 * Quotes a database identifier (table/column) correctly for the active connection.
 *
 * @param string $identifier The identifier to quote.
 * @param string|null $connection The connection name.
 * @return string The quoted identifier.
 */
function quote_identifier(string $identifier, ?string $connection = null): string
{
    $default_connection = function_exists('config') ? config('database.default', 'mysql') : 'mysql';
    $connection_name = $connection ?? $default_connection;

    if (!isset($GLOBALS['db_quoters'][$connection_name])) {
        db($connection_name); // Initializes the connection and its quoter
    }
    return $GLOBALS['db_quoters'][$connection_name]->quote($identifier);
}

/**
 * Build DSN string based on driver.
 */
function build_dsn(array $config): string
{
    $driver = $config['driver'];
    return match ($driver) {
        'mysql' => "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}",
        'pgsql' => "pgsql:host={$config['host']};port={$config['port']};dbname={$config['database']}",
        'sqlite' => "sqlite:{$config['database']}",
        default => throw new Exception("Unsupported database driver: {$driver}"),
    };
}


/**
 * Create a new query builder instance. A helper to start a fluent query.
 *
 * @param string $table The base table for the query.
 * @param string|null $connection The database connection name.
 * @return QueryBuilder
 */
function table(string $table, ?string $connection = null): QueryBuilder
{
    return new QueryBuilder($table, $connection);
}


/**
 * The Fluent Query Builder Class
 *
 * Provides a comprehensive, object-oriented way to build and execute SQL queries.
 */
class QueryBuilder
{
    private PDO $pdo;
    private ?string $connectionName;
    private Quoter $quoter;

    private string $table;
    private array $selects = [];
    private array $joins = [];
    private array $wheres = [];
    private array $havings = [];
    private array $bindings = [];
    private array $groupBy = [];
    private array $orderBy = [];
    private ?int $limit = null;
    private ?int $offset = null;
    private int $paramCount = 0;

    public function __construct(string $table, ?string $connection = null)
    {
        $this->table = $table;
        $this->connectionName = $connection;
        $this->pdo = db($connection);

        $default_connection = function_exists('config') ? config('database.default', 'mysql') : 'mysql';
        $connection_name = $connection ?? $default_connection;
        $this->quoter = $GLOBALS['db_quoters'][$connection_name];
    }

    private function q(string $identifier): string
    {
        return $this->quoter->quote($identifier);
    }

    private function newParam(mixed $value): string
    {
        $key = ":p" . ($this->paramCount++);
        $this->bindings[$key] = $value;
        return $key;
    }

    /**
     * Specifies the columns to be selected.
     * @param string|array $columns
     * @return $this
     */
    public function select(string|array $columns = ['*']): self
    {
        $this->selects = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    /**
     * Adds a basic WHERE clause or a grouped WHERE clause to the query.
     * MODIFIED: Now accepts a Closure for the first argument to create nested conditions.
     *
     * @param string|Closure $column
     * @param mixed $operator Can be omitted for '='.
     * @param mixed|null $value
     * @param string $boolean 'AND' or 'OR'
     * @return $this
     */
    public function where(string|Closure $column, mixed $operator = null, mixed $value = null, string $boolean = 'AND'): self
    {
        // --- NEW: Handle closure for grouped WHERE ---
        if ($column instanceof Closure) {
            $this->wheres[] = ['type' => 'Nested', 'query' => $column, 'boolean' => $boolean];
            return $this;
        }
        // --- END NEW ---

        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        $this->wheres[] = compact('column', 'operator', 'value', 'boolean');
        return $this;
    }

    /**
     * Adds an OR WHERE clause to the query.
     */
    public function orWhere(string $column, mixed $operator, mixed $value = null): self
    {
        return $this->where($column, $operator, $value, 'OR');
    }

    /**
     * Adds a WHERE ... IN (...) clause.
     */
    public function whereIn(string $column, array $values, string $boolean = 'AND'): self
    {
        $this->wheres[] = ['type' => 'In', 'column' => $column, 'values' => $values, 'boolean' => $boolean];
        return $this;
    }

    /**
     * Adds a WHERE ... NOT IN (...) clause.
     */
    public function whereNotIn(string $column, array $values, string $boolean = 'AND'): self
    {
        $this->wheres[] = ['type' => 'NotIn', 'column' => $column, 'values' => $values, 'boolean' => $boolean];
        return $this;
    }

    /**
     * Adds a WHERE NULL clause.
     */
    public function whereNull(string $column, string $boolean = 'AND'): self
    {
        $this->wheres[] = ['type' => 'Null', 'column' => $column, 'boolean' => $boolean];
        return $this;
    }

    /**
     * Adds a WHERE NOT NULL clause.
     */
    public function whereNotNull(string $column, string $boolean = 'AND'): self
    {
        $this->wheres[] = ['type' => 'NotNull', 'column' => $column, 'boolean' => $boolean];
        return $this;
    }

    /**
     * Adds a WHERE BETWEEN clause.
     */
    public function whereBetween(string $column, mixed $start, mixed $end, string $boolean = 'AND'): self
    {
        $this->wheres[] = ['type' => 'Between', 'column' => $column, 'start' => $start, 'end' => $end, 'boolean' => $boolean];
        return $this;
    }

    /**
     * Adds an ORDER BY clause.
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $direction = strtoupper($direction);
        if (!in_array($direction, ['ASC', 'DESC'])) {
            $direction = 'ASC';
        }
        $this->orderBy[] = "{$this->q($column)} {$direction}";
        return $this;
    }

    /**
     * Adds a GROUP BY clause.
     * @param string ...$columns
     * @return $this
     */
    public function groupBy(string ...$columns): self
    {
        foreach ($columns as $column) {
            $this->groupBy[] = $this->q($column);
        }
        return $this;
    }

    /**
     * Adds a HAVING clause. Works similarly to `where`.
     */
    public function having(string $column, mixed $operator, mixed $value = null, string $boolean = 'AND'): self
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        $this->havings[] = compact('column', 'operator', 'value', 'boolean');
        return $this;
    }

    /**
     * Adds a join clause.
     */
    public function join(string $table, string $firstColumn, string $operator, string $secondColumn, string $type = 'INNER'): self
    {
        $this->joins[] = "{$type} JOIN {$this->q($table)} ON {$this->q($firstColumn)} {$operator} {$this->q($secondColumn)}";
        return $this;
    }

    /**
     * Adds a LEFT JOIN clause.
     */
    public function leftJoin(string $table, string $firstColumn, string $operator, string $secondColumn): self
    {
        return $this->join($table, $firstColumn, $operator, $secondColumn, 'LEFT');
    }

    /**
     * Adds a RIGHT JOIN clause.
     */
    public function rightJoin(string $table, string $firstColumn, string $operator, string $secondColumn): self
    {
        return $this->join($table, $firstColumn, $operator, $secondColumn, 'RIGHT');
    }

    /**
     * Sets the LIMIT clause.
     */
    public function limit(int $count): self
    {
        $this->limit = $count;
        return $this;
    }

    /**
     * Sets the OFFSET clause.
     */
    public function offset(int $count): self
    {
        $this->offset = $count;
        return $this;
    }

    // --- Execution Methods ---

    /**
     * Executes the built SELECT query and returns all results.
     */
    public function get(): array
    {
        $sql = $this->toSql();
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->bindings);
        return $stmt->fetchAll();
    }

    /**
     * Executes the query and returns the first result.
     */
    public function first(): array|false
    {
        $this->limit(1);
        $sql = $this->toSql();
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->bindings);
        return $stmt->fetch();
    }

    /**
     * Executes a COUNT(*) query based on the current constraints.
     */
    public function count(): int
    {
        // Clone the builder to avoid modifying the original query's selects
        $clone = clone $this;
        $clone->selects = ["COUNT(*) as aggregate"];

        // Remove order by as it's not needed for count
        $clone->orderBy = [];

        $sql = $clone->toSql();
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($clone->bindings);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Paginate the results.
     * @param int $perPage Number of items per page.
     * @param int $page The current page number.
     * @return array An array containing pagination info and data.
     */
    public function paginate(int $perPage = 15, int $page = 1): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        // Create a separate query for the total count *before* applying limit/offset
        $countBuilder = clone $this;
        // Remove offset/limit/order from count query for performance
        $countBuilder->offset = null;
        $countBuilder->limit = null;
        $countBuilder->orderBy = [];
        $total = $countBuilder->count();

        // Now, get the actual data for the current page
        $this->limit($perPage)->offset($offset);
        $data = $this->get();

        return [
            'data' => $data,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => (int) ceil($total / $perPage),
                'from' => $offset + 1,
                'to' => $offset + count($data),
            ]
        ];
    }


    /**
     * Executes an INSERT statement.
     * REWRITTEN: Now intelligently handles both single-row and batch (multi-row) inserts.
     *
     * @param array $data For single insert: an associative array [column => value].
     *                    For batch insert: an array of associative arrays [[col=>val], [col=>val]].
     * @return int|string|false For single insert, returns lastInsertId().
     *                          For batch insert, returns the number of affected rows.
     *                          Returns false on failure.
     */
    public function insert(array $data): int|string|false
    {
        if (empty($data)) {
            return false;
        }

        // --- BATCH INSERT DETECTION ---
        // A batch insert is an array of arrays, where the outer array has numeric keys.
        $isBatch = isset($data[0]) && is_array($data[0]);

        if ($isBatch) {
            // --- BATCH INSERT LOGIC ---
            $firstRow = $data[0];
            $columns = array_keys($firstRow);
            $columnSql = implode(', ', array_map([$this, 'q'], $columns));

            $placeholders = [];
            $allValues = [];

            foreach ($data as $row) {
                // Create a group of positional placeholders like "(?, ?, ?)" for each row
                $placeholders[] = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
                // Add the values for this row to the flat list of bindings
                foreach ($columns as $column) {
                    $allValues[] = $row[$column] ?? null;
                }
            }

            $valuesSql = implode(', ', $placeholders);
            $sql = "INSERT INTO {$this->q($this->table)} ({$columnSql}) VALUES {$valuesSql}";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($allValues);

            // For batch inserts, return the number of affected rows.
            return $stmt->rowCount();

        } else {
            // --- SINGLE INSERT LOGIC (Original logic) ---
            $columns = implode(', ', array_map([$this, 'q'], array_keys($data)));
            $placeholders = implode(', ', array_map([$this, 'newParam'], array_values($data)));

            $sql = "INSERT INTO {$this->q($this->table)} ({$columns}) VALUES ({$placeholders})";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($this->bindings);

            // For single inserts, return the last inserted ID.
            return $this->pdo->lastInsertId();
        }
    }

    /**
     * Executes an UPDATE statement based on the query's WHERE clauses.
     * @param array $data Associative array of column => value.
     * @return int Number of affected rows.
     */
    public function update(array $data): int
    {
        if (empty($data)) {
            return 0;
        }

        $setClauses = [];
        foreach ($data as $column => $value) {
            $setClauses[] = "{$this->q($column)} = " . $this->newParam($value);
        }

        $sql = "UPDATE {$this->q($this->table)} SET " . implode(', ', $setClauses) . $this->compileWheres();

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->bindings);

        return $stmt->rowCount();
    }

    /**
     * Executes a DELETE statement based on the query's WHERE clauses.
     * @return int Number of affected rows.
     */
    public function delete(): int
    {
        $sql = "DELETE FROM {$this->q($this->table)}" . $this->compileWheres();

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->bindings);

        return $stmt->rowCount();
    }

    /**
     * Compiles the query into a SQL string.
     */
    public function toSql(): string
    {
        $sql = "SELECT ";
        $sql .= (empty($this->selects) ? '*' : implode(', ', $this->selects));
        $sql .= " FROM {$this->q($this->table)}";
        $sql .= $this->compileJoins();
        $sql .= $this->compileWheres();
        $sql .= $this->compileGroupBy();
        $sql .= $this->compileHavings();
        $sql .= $this->compileOrderBy();
        $sql .= $this->compileLimit();
        $sql .= $this->compileOffset();

        return $sql;
    }

    // --- SQL Compiler Methods ---

    private function compileWheres(): string
    {
        if (empty($this->wheres))
            return '';
        $sql = ' WHERE ';
        $first = true;
        foreach ($this->wheres as $where) {
            $boolean = $first ? '' : " {$where['boolean']} ";
            $type = $where['type'] ?? 'Basic';

            // --- MODIFIED: Switch to handle new 'Nested' type ---
            switch ($type) {
                case 'Nested':
                    // Create a new builder for the nested scope
                    $nestedBuilder = new QueryBuilder($this->table, $this->connectionName);
                    // Call the user's closure with the new builder
                    $where['query']($nestedBuilder);
                    // Get the compiled WHERE clause from the nested builder
                    $nestedSql = $nestedBuilder->compileWheres();

                    if (!empty($nestedSql)) {
                        // Merge bindings from the nested builder into the main one
                        $this->bindings = array_merge($this->bindings, $nestedBuilder->bindings);
                        // Add the nested clause, removing its "WHERE" and wrapping in parentheses
                        $sql .= "{$boolean}(" . substr($nestedSql, 7) . ")";
                    }
                    break;

                case 'In':
                case 'NotIn':
                    $placeholders = implode(', ', array_map([$this, 'newParam'], $where['values']));
                    $operator = $type === 'In' ? 'IN' : 'NOT IN';
                    $sql .= "{$boolean}{$this->q($where['column'])} {$operator} ({$placeholders})";
                    break;
                case 'Null':
                case 'NotNull':
                    $operator = $type === 'Null' ? 'IS NULL' : 'IS NOT NULL';
                    $sql .= "{$boolean}{$this->q($where['column'])} {$operator}";
                    break;
                case 'Between':
                    $start = $this->newParam($where['start']);
                    $end = $this->newParam($where['end']);
                    $sql .= "{$boolean}{$this->q($where['column'])} BETWEEN {$start} AND {$end}";
                    break;
                default: // Basic
                    $sql .= "{$boolean}{$this->q($where['column'])} {$where['operator']} {$this->newParam($where['value'])}";
                    break;
            }
            $first = false;
        }
        return $sql;
    }

    private function compileHavings(): string
    {
        if (empty($this->havings))
            return '';

        $sql = ' HAVING ';
        $first = true;
        foreach ($this->havings as $having) {
            $boolean = $first ? '' : " {$having['boolean']} ";
            $sql .= "{$boolean}{$this->q($having['column'])} {$having['operator']} {$this->newParam($having['value'])}";
            $first = false;
        }
        return $sql;
    }

    private function compileJoins(): string
    {
        return empty($this->joins) ? '' : ' ' . implode(' ', $this->joins);
    }

    private function compileGroupBy(): string
    {
        return empty($this->groupBy) ? '' : ' GROUP BY ' . implode(', ', $this->groupBy);
    }

    private function compileOrderBy(): string
    {
        return empty($this->orderBy) ? '' : ' ORDER BY ' . implode(', ', $this->orderBy);
    }

    private function compileLimit(): string
    {
        return $this->limit !== null ? " LIMIT {$this->limit}" : '';
    }

    private function compileOffset(): string
    {
        return $this->offset !== null ? " OFFSET {$this->offset}" : '';
    }
}