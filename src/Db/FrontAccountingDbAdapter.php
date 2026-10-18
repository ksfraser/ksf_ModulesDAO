<?php

namespace Ksfraser\ModulesDAO\Db;

use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

/**
 * Database adapter for FrontAccounting
 */
class FrontAccountingDbAdapter implements DbAdapterInterface
{
    private $tablePrefix;

    public function __construct(string $tablePrefix = '0_')
    {
        $this->tablePrefix = $tablePrefix;
    }

    public function getDialect(): string
    {
        return 'mysql';
    }

    public function getTablePrefix(): string
    {
        return $this->tablePrefix;
    }

    public function query(string $sql, array $params = []): array
    {
        // Replace named parameters with escaped values
        $sql = $this->substituteParams($sql, $params);
        
        // Use FA's db_query function. Pass a NULL error message so a failed
        // query does NOT trigger check_db_error(..., exit=true) -> end_page();
        // exit; which would kill the whole AJAX request from a module hook.
        $result = db_query($sql, null);
        if ($result === false && db_error_no() != 0) {
            global $db;
            throw new \RuntimeException('Database error ' . db_error_no() . ': ' . db_error_msg($db));
        }

        $rows = [];
        while ($row = db_fetch_assoc($result)) {
            $rows[] = $row;
        }

        return $rows;
    }

    public function execute(string $sql, array $params = []): void
    {
        // Replace named parameters with escaped values
        $sql = $this->substituteParams($sql, $params);
        
        // Use FA's db_query function. Pass a NULL error message so a failed
        // query does NOT trigger check_db_error(..., exit=true) -> end_page();
        // exit; which would kill the whole AJAX request from a module hook.
        $result = db_query($sql, null);
        if ($result === false && db_error_no() != 0) {
            global $db;
            throw new \RuntimeException('Database error ' . db_error_no() . ': ' . db_error_msg($db));
        }
    }

    private function substituteParams(string $sql, array $params): string
    {
        // Single regex pass over :name placeholders so a placeholder whose name
        // is a prefix of another (e.g. :manufacturer_duration inside
        // :manufacturer_duration_unit) cannot be corrupted by a shorter
        // replacement earlier in the loop.
        return preg_replace_callback(
            '/:([a-zA-Z_][a-zA-Z0-9_]*)/',
            function (array $matches) use ($params) {
                $key = $matches[1];
                if (!array_key_exists($key, $params)) {
                    return $matches[0]; // unknown placeholder — leave untouched
                }
                $value = $params[$key];
                if (is_null($value)) {
                    return 'NULL';
                }
                if (is_bool($value)) {
                    return $value ? '1' : '0';
                }
                if (is_int($value) || is_float($value)) {
                    return (string)$value;
                }
                // Strings MUST go through escape() so they stay quoted AND
                // escaped. FA's db_escape() is not a bare
                // mysqli_real_escape_string(): per includes/db/connect_db_mysqli.inc
                // it returns "'" . mysqli_real_escape_string(...) . "'", i.e. a
                // ready-to-use SQL literal. So it must be substituted BARE.
                //
                // Adding our own delimiters here produced doubly-quoted garbage
                // (`WHERE stock_id = ''048492035032''`, `VALUES (NULL, ''months'')`),
                // which MySQL parses as an empty string followed by a bare
                // token - a syntax error, or silently the wrong comparison.
                // The earlier "it only escapes, it does not add delimiters"
                // assumption was based on mysqli_real_escape_string, not db_escape.
                return $this->escape((string)$value);
            },
            $sql
        );
    }

    public function lastInsertId(): ?int
    {
        // Use FA's db_insert_id function
        return db_insert_id();
    }

    /**
     * Render a string as a quoted, escaped SQL literal.
     *
     * NOTE: FA's db_escape() already wraps the escaped value in single quotes
     * (includes/db/connect_db_mysqli.inc), so the result of this method is a
     * complete literal and must NOT be wrapped again by the caller.
     *
     * @param  string $value
     * @return string
     */
    public function escape(string $value): string
    {
        // Use FA's db_escape function
        return db_escape($value);
    }

    public function beginTransaction(): void
    {
        // FA doesn't have explicit transaction management in the same way
        // This is a no-op for FA
    }

    public function commit(): void
    {
        // FA doesn't have explicit transaction management in the same way
        // This is a no-op for FA
    }

    public function rollback(): void
    {
        // FA doesn't have explicit transaction management in the same way
        // This is a no-op for FA
    }
}