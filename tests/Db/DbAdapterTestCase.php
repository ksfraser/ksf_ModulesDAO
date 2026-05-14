<?php

namespace Ksfraser\ModulesDAO\Test\Db;

use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use Ksfraser\ModulesDAO\Db\FrontAccountingDbAdapter;
use PHPUnit\Framework\TestCase;

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

    public function testQueryReturnsArray(): void
    {
        $adapter = $this->createAdapter();
        $result = $adapter->query('SELECT 1 as test');
        $this->assertIsArray($result);
    }

    public function testExecuteDoesNotThrow(): void
    {
        $adapter = $this->createAdapter();
        $affected = $adapter->execute('SELECT 1');
        $this->assertIsInt($affected);
    }

    public function testLastInsertIdReturnsIntOrNull(): void
    {
        $adapter = $this->createAdapter();
        $result = $adapter->lastInsertId();
        $this->assertTrue(is_int($result) || is_null($result));
    }
}

class FrontAccountingDbAdapterTest extends DbAdapterTestCase
{
    protected function createAdapter(): DbAdapterInterface
    {
        return new FrontAccountingDbAdapter();
    }

    public function testConstructorWithPrefix(): void
    {
        $adapter = new FrontAccountingDbAdapter('custom_');
        $this->assertInstanceOf(FrontAccountingDbAdapter::class, $adapter);
    }

    public function testGetTablePrefixWithCustomPrefix(): void
    {
        $adapter = new FrontAccountingDbAdapter('custom_');
        $this->assertEquals('custom_', $adapter->getTablePrefix());
    }

    public function testGetTablePrefixWithEmptyPrefix(): void
    {
        $adapter = new FrontAccountingDbAdapter('');
        $this->assertEquals('', $adapter->getTablePrefix());
    }

    public function testEscapeSingleQuote(): void
    {
        $adapter = new FrontAccountingDbAdapter();
        $escaped = $adapter->escape("test's value");
        $this->assertEquals("test\\'s value", $escaped);
    }

    public function testEscapeDoubleQuote(): void
    {
        $adapter = new FrontAccountingDbAdapter();
        $escaped = $adapter->escape('test"value');
        $this->assertEquals('test"value', $escaped);
    }
}