<?php

namespace Ksfraser\ModulesDAO\Db;

class FrontAccountingDbAdapter implements DbAdapterInterface
{
    /** @var string */
    private $tablePrefix;

    public function __construct(string $tablePrefix = '')
    {
        $this->tablePrefix = $tablePrefix ?? '';
    }

    public function getDialect(): string
    {
        return 'mysql';
    }

    public function getTablePrefix(): string
    {
        return $this->tablePrefix;
    }

    public function escape(string $value): string
    {
        // db_escape(), not addslashes(). addslashes() is charset-unaware, so a
        // multi-byte sequence can swallow the escaping backslash, and it does
        // not cover every character MySQL treats specially. FA has no prepared
        // statements, so connection-aware escaping is the only defence.
        if (function_exists('db_escape')) {
            return (string)db_escape($value);
        }
        throw new \RuntimeException('db_escape() is unavailable; this adapter requires a FrontAccounting runtime.');
    }

    public function query(string $sql, array $params = []): array
    {
        if (!function_exists('db_query')) {
            return [];
        }

        $sql = $this->substituteParams($sql, $params);

        // A NULL error message keeps a failed query from reaching
        // check_db_error(..., exit=true) -> end_page(); exit; which would kill
        // the whole request, including a module hook or an AJAX response.
        $result = db_query($sql, null);
        if ($result === false && function_exists('db_error_no') && db_error_no() != 0) {
            global $db;
            $msg = function_exists('db_error_msg') && isset($db) ? db_error_msg($db) : '';
            throw new \RuntimeException('Database error ' . db_error_no() . ': ' . $msg);
        }

        $rows = [];
        if ($result) {
            while ($row = db_fetch_assoc($result)) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    public function execute(string $sql, array $params = []): int
    {
        if (!function_exists('db_query')) {
            return 0;
        }

        $sql = $this->substituteParams($sql, $params);

        $result = db_query($sql, null);
        if ($result === false && function_exists('db_error_no') && db_error_no() != 0) {
            global $db;
            $msg = function_exists('db_error_msg') && isset($db) ? db_error_msg($db) : '';
            throw new \RuntimeException('Database error ' . db_error_no() . ': ' . $msg);
        }

        return $result ? db_num_affected_rows() : 0;
    }

    /**
     * Substitute ? placeholders with correctly typed, escaped SQL literals.
     *
     * The previous loop wrapped every value in addslashes() and quotes, so an
     * int arrived as '5' and a null arrived as ''. Type each value properly:
     * only strings are quoted, and they are escaped with db_escape().
     *
     * @param  string $sql
     * @param  array  $params
     * @return string
     */
    private function substituteParams(string $sql, array $params): string
    {
        if (empty($params)) {
            return $sql;
        }

        // preg_quote the delimiter so a literal '?' in a value cannot be
        // mistaken for the next placeholder.
        foreach ($params as $param) {
            $sql = preg_replace(
                '/\?/',
                $this->delimReplacement($this->literal($param)),
                $sql,
                1
            );
        }

        return $sql;
    }

    /**
     * Escape the replacement so preg_replace does not interpret $ or \ in it.
     *
     * @param  string $replacement
     * @return string
     */
    private function delimReplacement(string $replacement): string
    {
        return str_replace('\\', '\\\\', $replacement);
    }

    /**
     * Render a PHP value as a SQL literal.
     *
     * @param  mixed $value
     * @return string
     */
    private function literal($value): string
    {
        if ($value === null) {
            return 'NULL';
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_int($value) || is_float($value)) {
            return (string)$value;
        }
        return "'" . $this->escape((string)$value) . "'";
    }

    public function lastInsertId(): ?int
    {
        if (!function_exists('db_insert_id')) {
            return null;
        }
        return db_insert_id();
    }
}
