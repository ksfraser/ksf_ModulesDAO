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
                // Strings MUST be quoted as well as escaped. escape() only
                // escapes; it does not add delimiters, so substituting its
                // return value bare emitted `WHERE stock_id = 048492035032`,
                // which MySQL reads as the integer 48492035032 and never matches
                // a varchar key. It also let a digit-only id such as '0000000'
                // collapse to 0. Quote here, matching MysqlDbAdapter::bindParams.
                return "'" . $this->escape((string)$value) . "'";
            },
            $sql
        );
    }

    public function lastInsertId(): ?int
    {
        // Use FA's db_insert_id function
        return db_insert_id();
    }

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