<?php

namespace Ksfraser\ModulesDAO\Tests\Unit\Sql;

use Ksfraser\ModulesDAO\Sql\LegacyArraySqlBuilder;
use PHPUnit\Framework\TestCase;

final class LegacyArraySqlBuilderTest extends TestCase
{
    public function testBuildSelectWithScalarEquals(): void
    {
        $q = LegacyArraySqlBuilder::buildSelect(
            ['*'],
            ['0_bank_accounts'],
            ['id' => 5]
        );

        $this->assertSame('SELECT * FROM 0_bank_accounts WHERE id = :w0', $q->getSql());
        $this->assertSame(['w0' => 5], $q->getParams());
    }

    public function testBuildSelectWithOperatorsAndInList(): void
    {
        $q = LegacyArraySqlBuilder::buildSelect(
            ['id', 'name'],
            ['users'],
            [
                'inactive' => ['eq', 0],
                'role' => ['in', ['admin', 'user']],
                'name' => ['like', 'bob'],
            ],
            null,
            null,
            ['name ASC'],
            10,
            20
        );

        $this->assertSame(
            'SELECT id, name FROM users WHERE inactive = :w0 AND role IN (:w1_0, :w1_1) AND name LIKE :w2 ORDER BY name ASC LIMIT 20, 10',
            $q->getSql()
        );
        $this->assertSame(
            [
                'w0' => 0,
                'w1_0' => 'admin',
                'w1_1' => 'user',
                'w2' => '%bob%',
            ],
            $q->getParams()
        );
    }

    public function testBuildSelectWithBetween(): void
    {
        $q = LegacyArraySqlBuilder::buildSelect(
            ['id'],
            ['orders'],
            ['order_date' => ['between', '2026-01-01', '2026-01-31']]
        );

        $this->assertSame('SELECT id FROM orders WHERE order_date BETWEEN :w0_a AND :w0_b', $q->getSql());
        $this->assertSame(['w0_a' => '2026-01-01', 'w0_b' => '2026-01-31'], $q->getParams());
    }

    public function testBuildSelectWithEmptyInProducesNoRowsClause(): void
    {
        $q = LegacyArraySqlBuilder::buildSelect(
            ['id'],
            ['t'],
            ['id' => ['in', []]]
        );

        $this->assertSame('SELECT id FROM t WHERE 1=0', $q->getSql());
        $this->assertSame([], $q->getParams());
    }

    public function testBuildInsertFromFieldsArray(): void
    {
        $fields = [
            ['name' => 'id', 'type' => 'int'],
            ['name' => 'name', 'type' => 'varchar(60)'],
            ['name' => 'inactive', 'type' => 'tinyint(1)'],
        ];
        $vars = ['id' => 10, 'name' => 'Bob', 'inactive' => 0, 'ignored' => 'x'];

        $q = \Ksfraser\ModulesDAO\Sql\LegacyArraySqlBuilder::buildInsert('0_bank_accounts', $fields, $vars);
        $this->assertSame('INSERT IGNORE INTO `0_bank_accounts` (`id`, `name`, `inactive`) VALUES (:v0, :v1, :v2)', $q->getSql());
        $this->assertSame(['v0' => 10, 'v1' => 'Bob', 'v2' => 0], $q->getParams());
    }

    public function testBuildUpdateFromFieldsArray(): void
    {
        $fields = [
            ['name' => 'id', 'type' => 'int'],
            ['name' => 'name', 'type' => 'varchar(60)'],
            ['name' => 'inactive', 'type' => 'tinyint(1)'],
        ];
        $vars = ['id' => 10, 'name' => 'Bob2', 'inactive' => 1];

        $q = \Ksfraser\ModulesDAO\Sql\LegacyArraySqlBuilder::buildUpdate('0_bank_accounts', 'id', $fields, $vars);
        $this->assertSame('UPDATE `0_bank_accounts` SET `name` = :v0, `inactive` = :v1 WHERE `id` = :pk', $q->getSql());
        $this->assertSame(['v0' => 'Bob2', 'v1' => 1, 'pk' => 10], $q->getParams());
    }

    public function testBuildDeleteByPrimaryKey(): void
    {
        $q = \Ksfraser\ModulesDAO\Sql\LegacyArraySqlBuilder::buildDelete('t', 'id', 123);
        $this->assertSame('DELETE FROM `t` WHERE `id` = :pk', $q->getSql());
        $this->assertSame(['pk' => 123], $q->getParams());
    }

    public function testBuildCreateTableSql(): void
    {
        $table = [
            'tablename' => '0_demo',
            'primarykey' => 'id',
            'engine' => 'MyISAM',
            'charset' => 'utf8',
        ];
        $fields = [
            ['name' => 'id', 'type' => 'int(11)', 'null' => 'NOT NULL', 'auto_increment' => true],
            ['name' => 'name', 'type' => 'varchar(60)', 'null' => 'NOT NULL', 'default' => "''"],
        ];

        $sql = \Ksfraser\ModulesDAO\Sql\LegacyArraySqlBuilder::buildCreateTableSql($table, $fields);
        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS `0_demo`', $sql);
        $this->assertStringContainsString('`id` int(11) NOT NULL AUTO_INCREMENT', $sql);
        $this->assertStringContainsString('PRIMARY KEY (`id`)', $sql);
        $this->assertStringContainsString('ENGINE=MyISAM', $sql);
        $this->assertStringContainsString('DEFAULT CHARSET=utf8', $sql);
    }
}
