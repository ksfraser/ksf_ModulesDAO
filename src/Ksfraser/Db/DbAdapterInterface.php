<?php

namespace Ksfraser\ModulesDAO\Db;

interface DbAdapterInterface
{
    public function getDialect(): string;
    public function getTablePrefix(): string;
    public function escape(string $value): string;
    public function query(string $sql, array $params = []): array;
    public function execute(string $sql, array $params = []): int;
    public function lastInsertId(): ?int;
}