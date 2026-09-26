<?php

/**
 * Global DAO wrapper for FA db functions
 * Part of ksf_ModulesDAO library
 */
class ksf_ModulesDAO
{
    public function query($sql, $params = [])
    {
        if (!empty($params)) {
            // Simple parameter replacement, assuming ? placeholders
            foreach ($params as $param) {
                $sql = preg_replace('/\?/', $this->literal($param), $sql, 1);
            }
        }
        return db_query($sql, "DAO query failed");
    }

    /**
     * Render a PHP value as a SQL literal.
     *
     * Strings go through FA's db_escape(), which wraps mysqli_real_escape_string
     * for the current connection and charset. addslashes() is NOT an acceptable
     * substitute: it is charset-unaware, so a multi-byte sequence can be made to
     * swallow the escaping backslash, and it does not escape every character
     * MySQL treats specially. FA has no prepared statements, so escaping is the
     * only defence and it has to be the connection-aware one.
     *
     * @param  mixed $value
     * @return string
     */
    private function literal($value)
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
        return "'" . db_escape((string)$value) . "'";
    }

    public function affectedRows()
    {
        return db_num_affected_rows();
    }

    public function beginTransaction()
    {
        db_query("START TRANSACTION");
    }

    public function commit()
    {
        db_query("COMMIT");
    }

    public function rollback()
    {
        db_query("ROLLBACK");
    }
}
