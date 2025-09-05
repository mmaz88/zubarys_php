<?php // core/db/Quoter.php

declare(strict_types=1);

/**
 * Defines the contract for a database identifier quoter.
 * This allows the StarterKit to handle different SQL syntax (e.g., ` vs ")
 * for different database drivers.
 */
interface Quoter
{
    /**
     * Quotes a table or column name according to the driver's rules.
     * @param string $identifier The identifier to quote.
     * @return string The quoted identifier.
     */
    public function quote(string $identifier): string;
}

/**
 * Handles quoting for MySQL and SQLite, which both use backticks.
 */
class MySqlQuoter implements Quoter
{
    public function quote(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }
}

/**
 * Handles quoting for PostgreSQL, which uses double quotes.
 */
class PostgresQuoter implements Quoter
{
    public function quote(string $identifier): string
    {
        return '"' . str_replace('"', '""', $identifier) . '"';
    }
}

/**
 * A fallback quoter that does nothing, for unknown drivers.
 */
class PassthroughQuoter implements Quoter
{
    public function quote(string $identifier): string
    {
        return $identifier;
    }
}