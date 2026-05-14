<?php

namespace Ksfraser\ModulesDAO\Db;

class FrontAccountingDbAdapter implements DbAdapterInterface
{
    private string $tablePrefix;
    
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
        return addslashes($value);
    }
    
    public function query(string $sql, array $params = []): array
    {
        if (!function_exists('db_query')) {
            return [];
        }
        
        if (!empty($params)) {
            foreach ($params as $param) {
                $sql = preg_replace('/\?/', "'" . addslashes($param) . "'", $sql, 1);
            }
        }
        
        $result = db_query($sql, "DAO query failed");
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
        
        if (!empty($params)) {
            foreach ($params as $param) {
                $sql = preg_replace('/\?/', "'" . addslashes($param) . "'", $sql, 1);
            }
        }
        
        $result = db_query($sql, "DAO execute failed");
        return $result ? db_num_affected_rows() : 0;
    }
    
    public function lastInsertId(): ?int
    {
        if (!function_exists('db_insert_id')) {
            return null;
        }
        return db_insert_id();
    }
}