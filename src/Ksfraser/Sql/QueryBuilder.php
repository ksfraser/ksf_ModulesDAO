<?php

declare(strict_types=1);

namespace Ksfraser\ModulesDAO\Sql;

use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

final class QueryBuilder
{
    private DbAdapterInterface $db;
    private string $prefix;

    private array $select = ['*'];
    private string $from;
    private string $fromAlias = '';
    private array $joins = [];
    private array $where = [];
    private array $params = [];
    private array $groupBy = [];
    private array $having = [];
    private array $orderBy = [];
    private ?int $limit = null;
    private ?int $offset = null;
    public function __construct(DbAdapterInterface $db)
    {
        $this->db = $db;
        $this->prefix = $db->getTablePrefix();
    }

    public function select(...$columns): self
    {
        $this->select = $columns;
        return $this;
    }

    public function addSelect(...$columns): self
    {
        $this->select = array_merge($this->select, $columns);
        return $this;
    }

    public function from(string $table, string $alias = ''): self
    {
        $this->from = $this->prefix . $table;
        $this->fromAlias = $alias;
        return $this;
    }

    public function fromRaw(string $tableExpression): self
    {
        $this->from = $tableExpression;
        return $this;
    }

    public function join(string $table, string $on, string $alias = '', string $type = 'INNER'): self
    {
        $this->joins[] = [
            'type' => $type,
            'table' => $this->prefix . $table,
            'alias' => $alias,
            'on' => $on,
        ];
        return $this;
    }

    public function leftJoin(string $table, string $on, string $alias = ''): self
    {
        return $this->join($table, $on, $alias, 'LEFT');
    }

    public function rightJoin(string $table, string $on, string $alias = ''): self
    {
        return $this->join($table, $on, $alias, 'RIGHT');
    }

    public function where(string $column, $operator = null, $value = null): self
    {
        if ($operator === null && $value === null) {
            $this->where[] = ['type' => 'raw', 'sql' => $column];
            return $this;
        }

        if ($value === null && is_string($operator)) {
            $opUpper = strtoupper($operator);
            if ($opUpper === 'IS NULL' || $opUpper === 'IS NOT NULL') {
                $this->where[] = ['type' => 'raw', 'sql' => "$column $opUpper"];
                return $this;
            }
            if ($opUpper === 'RAW') {
                $this->where[] = ['type' => 'raw', 'sql' => $column];
                return $this;
            }
        }

        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $operator = strtoupper($operator);

        if ($operator === 'IN' || $operator === 'NOT IN') {
            $values = is_array($value) ? array_values($value) : [$value];
            $placeholders = [];
            foreach ($values as $v) {
                $placeholders[] = '?';
                $this->params[] = $v;
            }
            if (empty($values)) {
                $this->where[] = ['type' => 'raw', 'sql' => $operator === 'IN' ? '1=0' : '1=1'];
            } else {
                $this->where[] = [
                    'type' => 'raw',
                    'sql' => "$column $operator (" . implode(', ', $placeholders) . ')',
                ];
            }
            return $this;
        }

        if ($operator === 'BETWEEN' || $operator === 'NOT BETWEEN') {
            $values = is_array($value) ? array_values($value) : [$value, $value];
            $this->params[] = $values[0];
            $this->params[] = $values[1];
            $this->where[] = ['type' => 'raw', 'sql' => "$column $operator ? AND ?"];
            return $this;
        }

        if ($operator === 'IS NULL' || $operator === 'IS NOT NULL') {
            $this->where[] = ['type' => 'raw', 'sql' => "$column $operator"];
            return $this;
        }

        if ($operator === 'RAW') {
            $this->where[] = ['type' => 'raw', 'sql' => $column];
            if (is_array($value)) {
                foreach ($value as $v) {
                    $this->params[] = $v;
                }
            } elseif ($value !== null) {
                $this->params[] = $value;
            }
            return $this;
        }

        $this->params[] = $value;
        $this->where[] = ['type' => 'raw', 'sql' => "$column $operator ?"];
        return $this;
    }

    public function whereNull(string $column): self
    {
        $this->where[] = ['type' => 'raw', 'sql' => "$column IS NULL"];
        return $this;
    }

    public function whereNotNull(string $column): self
    {
        $this->where[] = ['type' => 'raw', 'sql' => "$column IS NOT NULL"];
        return $this;
    }

    public function andWhere(string $column, $operator = null, $value = null): self
    {
        return $this->where($column, $operator, $value);
    }

    public function orWhere(string $column, $operator = null, $value = null): self
    {
        if ($operator === null && $value === null) {
            $this->where[] = ['type' => 'or_raw', 'sql' => $column];
            return $this;
        }

        if ($value === null && is_string($operator)) {
            $opUpper = strtoupper($operator);
            if ($opUpper === 'IS NULL' || $opUpper === 'IS NOT NULL') {
                $this->where[] = ['type' => 'or_raw', 'sql' => "$column $opUpper"];
                return $this;
            }
            if ($opUpper === 'RAW') {
                $this->where[] = ['type' => 'or_raw', 'sql' => $column];
                return $this;
            }
        }

        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $operator = strtoupper($operator);

        if ($operator === 'IN' || $operator === 'NOT IN') {
            $values = is_array($value) ? array_values($value) : [$value];
            $placeholders = [];
            foreach ($values as $v) {
                $placeholders[] = '?';
                $this->params[] = $v;
            }
            if (empty($values)) {
                $this->where[] = ['type' => 'or_raw', 'sql' => $operator === 'IN' ? '1=0' : '1=1'];
            } else {
                $this->where[] = [
                    'type' => 'or_raw',
                    'sql' => "$column $operator (" . implode(', ', $placeholders) . ')',
                ];
            }
            return $this;
        }

        if ($operator === 'BETWEEN' || $operator === 'NOT BETWEEN') {
            $values = is_array($value) ? array_values($value) : [$value, $value];
            $this->params[] = $values[0];
            $this->params[] = $values[1];
            $this->where[] = ['type' => 'or_raw', 'sql' => "$column $operator ? AND ?"];
            return $this;
        }

        if ($operator === 'IS NULL' || $operator === 'IS NOT NULL') {
            $this->where[] = ['type' => 'or_raw', 'sql' => "$column $operator"];
            return $this;
        }

        $this->params[] = $value;
        $this->where[] = ['type' => 'or_raw', 'sql' => "$column $operator ?"];
        return $this;
    }

    public function groupBy(...$columns): self
    {
        $this->groupBy = $columns;
        return $this;
    }

    public function having(string $column, string $operator, $value): self
    {
        $operator = strtoupper($operator);
        $this->params[] = $value;
        $this->having[] = "$column $operator ?";
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->orderBy[] = "$column $direction";
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    public function page(int $page, int $perPage = 25): self
    {
        $this->limit = $perPage;
        $this->offset = ($page - 1) * $perPage;
        return $this;
    }

    public function getQuery(): BuiltQuery
    {
        return new BuiltQuery($this->buildSelectSql(), $this->params);
    }

    public function get(): array
    {
        return $this->db->query($this->buildSelectSql(), $this->params);
    }

    public function one(): ?array
    {
        $qb = clone $this;
        $qb->limit(1);
        $rows = $qb->get();
        return $rows[0] ?? null;
    }

    public function count(): int
    {
        $qb = clone $this;
        $sql = 'SELECT COUNT(*) AS cnt FROM ' . $qb->from;
        if ($qb->fromAlias !== '') {
            $sql .= ' AS ' . $qb->fromAlias;
        }
        foreach ($qb->joins as $join) {
            $sql .= " {$join['type']} JOIN {$join['table']}";
            if ($join['alias'] !== '') {
                $sql .= ' AS ' . $join['alias'];
            }
            $sql .= " ON {$join['on']}";
        }
        if (!empty($qb->where)) {
            $sql .= ' WHERE' . $this->buildWhereClause($qb->where);
        }
        $rows = $this->db->query($sql, $this->params);
        return !empty($rows) ? (int)$rows[0]['cnt'] : 0;
    }

    public function exists(): bool
    {
        return $this->count() > 0;
    }

    public function toSql(): string
    {
        return $this->buildSelectSql();
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function reset(): self
    {
        $this->select = ['*'];
        $this->from = '';
        $this->fromAlias = '';
        $this->joins = [];
        $this->where = [];
        $this->params = [];
        $this->groupBy = [];
        $this->having = [];
        $this->orderBy = [];
        $this->limit = null;
        $this->offset = null;
        return $this;
    }

    public function insert(string $table, array $data): self
    {
        $qb = new self($this->db);
        $cols = [];
        $phs = [];
        $params = [];
        foreach ($data as $col => $val) {
            $cols[] = "`$col`";
            $phs[] = '?';
            $params[] = $val;
        }
        $qb->from = 'INSERT INTO ' . $qb->prefix . $table
            . ' (' . implode(', ', $cols) . ')'
            . ' VALUES (' . implode(', ', $phs) . ')';
        $qb->params = $params;
        $qb->select = [];
        return $qb;
    }

    public function update(string $table, array $data): self
    {
        $qb = new self($this->db);
        $sets = [];
        $params = [];
        foreach ($data as $col => $val) {
            $sets[] = "`$col` = ?";
            $params[] = $val;
        }
        $qb->from = 'UPDATE ' . $qb->prefix . $table . ' SET ' . implode(', ', $sets);
        $qb->params = $params;
        $qb->select = [];
        return $qb;
    }

    public function delete(string $table): self
    {
        $qb = new self($this->db);
        $qb->from = 'DELETE FROM ' . $qb->prefix . $table;
        $qb->select = [];
        return $qb;
    }

    public function execute(): int
    {
        return $this->db->execute($this->buildSelectSql(), $this->params);
    }

    public function lastInsertId(): ?int
    {
        return $this->db->lastInsertId();
    }

    private function buildSelectSql(): string
    {
        if (str_starts_with($this->from, 'INSERT')) {
            return $this->from;
        }

        if (str_starts_with($this->from, 'UPDATE') || str_starts_with($this->from, 'DELETE')) {
            $sql = $this->from;
            if (!empty($this->where)) {
                $sql .= ' WHERE' . $this->buildWhereClause($this->where);
            }
            return $sql;
        }

        if (str_starts_with($this->from, 'SELECT')) {
            return $this->from;
        }

        $sql = 'SELECT ' . implode(', ', $this->select);
        $sql .= ' FROM ' . $this->from;

        if ($this->fromAlias !== '') {
            $sql .= ' AS ' . $this->fromAlias;
        }

        foreach ($this->joins as $join) {
            $sql .= " {$join['type']} JOIN {$join['table']}";
            if ($join['alias'] !== '') {
                $sql .= ' AS ' . $join['alias'];
            }
            $sql .= " ON {$join['on']}";
        }

        if (!empty($this->where)) {
            $sql .= ' WHERE' . $this->buildWhereClause($this->where);
        }

        if (!empty($this->groupBy)) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groupBy);
        }

        if (!empty($this->having)) {
            $sql .= ' HAVING ' . implode(' AND ', $this->having);
        }

        if (!empty($this->orderBy)) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orderBy);
        }

        if ($this->limit !== null) {
            if ($this->offset !== null && $this->offset > 0) {
                $sql .= ' LIMIT ' . (int)$this->offset . ', ' . (int)$this->limit;
            } else {
                $sql .= ' LIMIT ' . (int)$this->limit;
            }
        }

        return $sql;
    }

    private function buildWhereClause(array $where): string
    {
        $result = '';
        $first = true;
        foreach ($where as $w) {
            if ($first) {
                $result .= ' ' . $w['sql'];
                $first = false;
            } else {
                $result .= ($w['type'] === 'or_raw' ? ' OR ' : ' AND ') . $w['sql'];
            }
        }
        return $result;
    }

    public function __clone()
    {
        $this->params = $this->params;
    }
}
