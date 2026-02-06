<?php

namespace Ksfraser\ModulesDAO\Test\Db;

use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

/**
 * Abstract test for DbAdapterInterface implementations
 */
abstract class DbAdapterTestCase extends TestCase
{
    abstract protected function createAdapter(): DbAdapterInterface;

    public function testImplementsInterface(): void
    {
        $adapter = $this->createAdapter();
        $this->assertInstanceOf(DbAdapterInterface::class, $adapter);
    }

    public function testGetDialectReturnsString(): void
    {
        $adapter = $this->createAdapter();
        $dialect = $adapter->getDialect();
        $this->assertIsString($dialect);
        $this->assertNotEmpty($dialect);
    }

    public function testGetTablePrefixReturnsString(): void
    {
        $adapter = $this->createAdapter();
        $prefix = $adapter->getTablePrefix();
        $this->assertIsString($prefix);
    }

    public function testEscapeReturnsString(): void
    {
        $adapter = $this->createAdapter();
        $escaped = $adapter->escape("test'value");
        $this->assertIsString($escaped);
    }
}