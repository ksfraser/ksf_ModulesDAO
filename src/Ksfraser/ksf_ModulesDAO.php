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
        return "'" . addslashes((string)$value) . "'";
    }
}
