<?php

namespace Ksfraser\ModulesDAO\Sql;

/**
 * SQL builder compatible with the historical "array parts" approach used by ksf_modules_common.
 *
 * This is intended as a migration bridge:
 * - schemas (fields_array/table_details) stay close to the FA-specific code
 * - query building lives in ModulesDAO to avoid duplication
 *
 * Where operators supported (legacy): lt, lte, gt, gte, eq, neq, like, in, between, betweenf.
 */
final class LegacyArraySqlBuilder
{
    /**
     * @param array<int, string> $select
     * @param array<int, string> $from
     * @param array<string, mixed>|null $where
     * @param array<int, string>|null $groupBy
     * @param array<int, string>|null $having
     * @param array<int, string>|null $orderBy
     */
    public static function buildSelect(
        array $select,
        array $from,
        ?array $where = null,
        ?array $groupBy = null,
        ?array $having = null,
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null
    ): BuiltQuery {
        $params = [];

        $sql = 'SELECT ' . self::csv($select)
            . ' FROM ' . self::csv($from);

        if ($where !== null && count($where) > 0) {
            [$whereSql, $whereParams] = self::buildWhere($where);
            $sql .= ' WHERE ' . $whereSql;
            $params = $whereParams;
        }

        if ($groupBy !== null && count($groupBy) > 0) {
            $sql .= ' GROUP BY ' . self::csv($groupBy);
        }

        if ($having !== null && count($having) > 0) {
            // NOTE: legacy code treated having as a list of raw expressions.
            $sql .= ' HAVING ' . self::csv($having);
        }

        if ($orderBy !== null && count($orderBy) > 0) {
            $sql .= ' ORDER BY ' . self::csv($orderBy);
        }

        if ($limit !== null) {
            if ($offset !== null && $offset > 0) {
                $sql .= ' LIMIT ' . (int)$offset . ', ' . (int)$limit;
            } else {
                $sql .= ' LIMIT ' . (int)$limit;
            }
        }

        return new BuiltQuery($sql, $params);
    }

    /**
     * @param array<string, mixed> $where
     * @return array{0:string,1:array<string,mixed>}
     */
    private static function buildWhere(array $where): array
    {
        $parts = [];
        $params = [];
        $i = 0;

        foreach ($where as $col => $val) {
            if (is_array($val)) {
                // Legacy shape: [op, value] or [op, v1, v2]
                $op = (string)($val[0] ?? '');
                $op = strtolower($op);
                switch ($op) {
                    case 'lt':
                    case 'lte':
                    case 'gt':
                    case 'gte':
                    case 'eq':
                    case 'neq':
                        $map = [
                            'lt' => '<',
                            'lte' => '<=',
                            'gt' => '>',
                            'gte' => '>=',
                            'eq' => '=',
                            'neq' => '<>',
                        ];
                        $ph = ':w' . $i;
                        $params[ltrim($ph, ':')] = $val[1] ?? null;
                        $parts[] = "$col {$map[$op]} $ph";
                        $i++;
                        break;

                    case 'like':
                        $ph = ':w' . $i;
                        $params[ltrim($ph, ':')] = '%' . (string)($val[1] ?? '') . '%';
                        $parts[] = "$col LIKE $ph";
                        $i++;
                        break;

                    case 'in':
                        $inVal = $val[1] ?? [];
                        if (is_string($inVal)) {
                            // legacy sometimes supplies a raw string like "( 'a', 'b' )" or a subquery
                            $parts[] = "$col IN $inVal";
                            break;
                        }
                        if (!is_array($inVal) || count($inVal) === 0) {
                            // empty IN should return no rows
                            $parts[] = '1=0';
                            break;
                        }
                        $phs = [];
                        foreach (array_values($inVal) as $j => $item) {
                            $ph = ':w' . $i . '_' . $j;
                            $params[ltrim($ph, ':')] = $item;
                            $phs[] = $ph;
                        }
                        $parts[] = "$col IN (" . implode(', ', $phs) . ')';
                        $i++;
                        break;

                    case 'between':
                        $ph1 = ':w' . $i . '_a';
                        $ph2 = ':w' . $i . '_b';
                        $params[ltrim($ph1, ':')] = $val[1] ?? null;
                        $params[ltrim($ph2, ':')] = $val[2] ?? null;
                        $parts[] = "$col BETWEEN $ph1 AND $ph2";
                        $i++;
                        break;

                    case 'betweenf':
                        // FORMATTED betweens e.g. DATE_ADD/DATE_SUB; treated as raw
                        $left = (string)($val[1] ?? '');
                        $right = (string)($val[2] ?? '');
                        $parts[] = "$col BETWEEN $left AND $right";
                        break;

                    default:
                        throw new \InvalidArgumentException('Unsupported where operator: ' . $op);
                }
            } else {
                $ph = ':w' . $i;
                $params[ltrim($ph, ':')] = $val;
                $parts[] = "$col = $ph";
                $i++;
            }
        }

        return [implode(' AND ', $parts), $params];
    }

    /**
     * @param array<int, string> $parts
     */
    private static function csv(array $parts): string
    {
        return implode(', ', array_values($parts));
    }

    /**
     * Build an INSERT statement from a legacy fields_array and an object's vars.
     *
     * @param array<int, array{name:string, type?:string, null?:string, default?:string, auto_increment?:mixed}> $fieldsArray
     * @param array<string, mixed> $objectVars
     */
    public static function buildInsert(string $tableName, array $fieldsArray, array $objectVars, bool $ignore = true): BuiltQuery
    {
        $cols = [];
        $phs = [];
        $params = [];
        $i = 0;

        foreach ($fieldsArray as $field) {
            $name = isset($field['name']) ? trim((string)$field['name']) : '';
            if ($name === '') {
                continue;
            }
            if (!array_key_exists($name, $objectVars)) {
                continue;
            }
            $cols[] = self::bt($name);
            $ph = ':v' . $i;
            $phs[] = $ph;
            $params[ltrim($ph, ':')] = $objectVars[$name];
            $i++;
        }

        if (count($cols) === 0) {
            throw new \InvalidArgumentException('No values provided for insert');
        }

        $verb = $ignore ? 'INSERT IGNORE' : 'INSERT';
        $sql = $verb . ' INTO ' . self::bt($tableName)
            . ' (' . implode(', ', $cols) . ')'
            . ' VALUES (' . implode(', ', $phs) . ')';

        return new BuiltQuery($sql, $params);
    }

    /**
     * Build an UPDATE statement from a legacy fields_array and an object's vars.
     *
     * @param array<int, array{name:string, type?:string, null?:string, default?:string, auto_increment?:mixed}> $fieldsArray
     * @param array<string, mixed> $objectVars
     */
    public static function buildUpdate(string $tableName, string $primaryKey, array $fieldsArray, array $objectVars): BuiltQuery
    {
        $primaryKey = trim($primaryKey);
        if ($primaryKey === '') {
            throw new \InvalidArgumentException('Primary key not provided');
        }
        if (!array_key_exists($primaryKey, $objectVars)) {
            throw new \InvalidArgumentException('Primary key value not provided');
        }

        $assignments = [];
        $params = [];
        $i = 0;

        foreach ($fieldsArray as $field) {
            $name = isset($field['name']) ? trim((string)$field['name']) : '';
            if ($name === '' || $name === $primaryKey) {
                continue;
            }
            if (!array_key_exists($name, $objectVars)) {
                continue;
            }
            $ph = ':v' . $i;
            $assignments[] = self::bt($name) . ' = ' . $ph;
            $params[ltrim($ph, ':')] = $objectVars[$name];
            $i++;
        }

        if (count($assignments) === 0) {
            throw new \InvalidArgumentException('No values provided for update');
        }

        $pkPh = ':pk';
        $params[ltrim($pkPh, ':')] = $objectVars[$primaryKey];

        $sql = 'UPDATE ' . self::bt($tableName)
            . ' SET ' . implode(', ', $assignments)
            . ' WHERE ' . self::bt($primaryKey) . ' = ' . $pkPh;

        return new BuiltQuery($sql, $params);
    }

    public static function buildDelete(string $tableName, string $primaryKey, $primaryKeyValue): BuiltQuery
    {
        $pk = trim($primaryKey);
        if ($pk === '') {
            throw new \InvalidArgumentException('Primary key not provided');
        }
        return new BuiltQuery(
            'DELETE FROM ' . self::bt($tableName) . ' WHERE ' . self::bt($pk) . ' = :pk',
            ['pk' => $primaryKeyValue]
        );
    }

    /**
     * Build CREATE TABLE statement from legacy table_details + fields_array.
     *
     * @param array{tablename:string, primarykey?:string, engine?:string, charset?:string, index?:array<int,array{type?:string,keyname?:string,columns?:string}>} $tableDetails
     * @param array<int, array{name:string, type:string, null?:string, default?:string, auto_increment?:mixed}> $fieldsArray
     */
    public static function buildCreateTableSql(array $tableDetails, array $fieldsArray, bool $ifNotExists = true): string
    {
        if (!isset($tableDetails['tablename']) || trim((string)$tableDetails['tablename']) === '') {
            throw new \InvalidArgumentException('table_details.tablename not set');
        }
        $tableName = trim((string)$tableDetails['tablename']);

        $sql = 'CREATE TABLE ' . ($ifNotExists ? 'IF NOT EXISTS ' : '') . self::bt($tableName) . " (\n";

        $cols = [];
        foreach ($fieldsArray as $row) {
            $name = isset($row['name']) ? trim((string)$row['name']) : '';
            $type = isset($row['type']) ? trim((string)$row['type']) : '';
            if ($name === '' || $type === '') {
                continue;
            }
            $col = self::bt($name) . ' ' . $type;
            if (isset($row['null']) && trim((string)$row['null']) !== '') {
                $col .= ' ' . trim((string)$row['null']);
            }
            if (isset($row['auto_increment'])) {
                $col .= ' AUTO_INCREMENT';
            }
            if (isset($row['default']) && trim((string)$row['default']) !== '') {
                $col .= ' DEFAULT ' . trim((string)$row['default']);
            }
            $cols[] = $col;
        }

        if (isset($tableDetails['primarykey']) && trim((string)$tableDetails['primarykey']) !== '') {
            $cols[] = 'PRIMARY KEY (' . self::bt(trim((string)$tableDetails['primarykey'])) . ')';
        }

        if (isset($tableDetails['index']) && is_array($tableDetails['index'])) {
            foreach ($tableDetails['index'] as $index) {
                $type = strtolower((string)($index['type'] ?? ''));
                $keyName = (string)($index['keyname'] ?? '');
                $columns = (string)($index['columns'] ?? '');
                if ($keyName === '' || $columns === '') {
                    continue;
                }
                if ($type === 'unique') {
                    $cols[] = 'UNIQUE KEY ' . self::bt($keyName) . ' ( ' . $columns . ' )';
                } else {
                    // legacy used UNIQUE KEY for non-unique too; preserve behavior
                    $cols[] = 'UNIQUE KEY ' . self::bt($keyName) . ' ( ' . $columns . ' )';
                }
            }
        }

        if (count($cols) === 0) {
            throw new \InvalidArgumentException('No columns provided for create table');
        }

        $sql .= implode(",\n", $cols) . "\n)";

        $engine = isset($tableDetails['engine']) && trim((string)$tableDetails['engine']) !== ''
            ? trim((string)$tableDetails['engine'])
            : 'MyISAM';
        $charset = isset($tableDetails['charset']) && trim((string)$tableDetails['charset']) !== ''
            ? trim((string)$tableDetails['charset'])
            : 'utf8';

        $sql .= ' ENGINE=' . $engine . ' DEFAULT CHARSET=' . $charset;
        return $sql;
    }

    /**
     * This mirrors the legacy behavior (ADD COLUMN (...) with all columns), which may fail
     * if columns already exist. Kept as a migration helper only.
     *
     * @param array{tablename:string} $tableDetails
     * @param array<int, array{name:string, type:string, null?:string, default?:string, auto_increment?:mixed}> $fieldsArray
     */
    public static function buildAlterTableAddColumnsSql(array $tableDetails, array $fieldsArray): string
    {
        if (!isset($tableDetails['tablename']) || trim((string)$tableDetails['tablename']) === '') {
            throw new \InvalidArgumentException('table_details.tablename not set');
        }
        $tableName = trim((string)$tableDetails['tablename']);

        $colParts = [];
        foreach ($fieldsArray as $row) {
            $name = isset($row['name']) ? trim((string)$row['name']) : '';
            $type = isset($row['type']) ? trim((string)$row['type']) : '';
            if ($name === '' || $type === '') {
                continue;
            }
            $col = self::bt($name) . ' ' . $type;
            if (isset($row['null']) && trim((string)$row['null']) !== '') {
                $col .= ' ' . trim((string)$row['null']);
            }
            if (isset($row['auto_increment'])) {
                $col .= ' AUTO_INCREMENT';
            }
            if (isset($row['default']) && trim((string)$row['default']) !== '') {
                $col .= ' DEFAULT ' . trim((string)$row['default']);
            }
            $colParts[] = $col;
        }

        if (count($colParts) === 0) {
            throw new \InvalidArgumentException('No columns provided for alter table');
        }

        return 'ALTER TABLE ' . self::bt($tableName) . "\n" . 'ADD COLUMN (' . implode(', ', $colParts) . ')';
    }

    private static function bt(string $identifier): string
    {
        // If it looks like an expression (contains whitespace, dot, parentheses, backticks) don't wrap.
        if (preg_match('/[\s\.`()]/', $identifier)) {
            return $identifier;
        }
        return '`' . $identifier . '`';
    }
}
