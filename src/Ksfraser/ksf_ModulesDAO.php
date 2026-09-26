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
            // Pre-split on '?' placeholders BEFORE substitution so literal
            // '?' characters inside parameter values (e.g. URLs in raw_json)
            // cannot collide with subsequent placeholder replacements.
            $parts = explode('?', $sql);
            if (count($parts) === count($params) + 1) {
                $built = array_shift($parts);
                foreach ($parts as $i => $part) {
                    $built .= $this->quoteValue($params[$i]) . $part;
                }
                $sql = $built;
            } else {
                // Fallback for SQL strings that legitimately contain '?' literals
                foreach ($params as $param) {
                    $sql = preg_replace('/\?/', $this->quoteValue($param), $sql, 1);
                }
            }
        }
        return db_query($sql, "DAO query failed");
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

    private function quoteValue($value)
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
        // db_escape(), not addslashes(): FA has no prepared statements, so
        // connection-aware escaping is the only defence, and addslashes() is
        // charset-unaware.
        $str = (string)$value;
        if (function_exists('db_escape')) {
            return "'" . db_escape($str) . "'";
        }
        throw new \RuntimeException('db_escape() is unavailable; literal rendering requires a FrontAccounting runtime.');
    }
}
