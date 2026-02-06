<?php

namespace Ksfraser\ModulesDAO\Test\Db;

use Ksfraser\ModulesDAO\Db\FrontAccountingDbAdapter;

/**
 * Test for FrontAccountingDbAdapter
 */
class FrontAccountingDbAdapterTest extends DbAdapterTestCase
{
    protected function createAdapter(): \Ksfraser\ModulesDAO\Db\DbAdapterInterface
    {
        return new FrontAccountingDbAdapter();
    }

    public function testConstructorWithPrefix(): void
    {
        $adapter = new FrontAccountingDbAdapter('custom_');
        $this->assertInstanceOf(FrontAccountingDbAdapter::class, $adapter);
    }

    public function testGetDialect(): void
    {
        $adapter = $this->createAdapter();
        $this->assertEquals('mysql', $adapter->getDialect());
    }

    public function testGetTablePrefix(): void
    {
        $adapter = $this->createAdapter();
        $prefix = $adapter->getTablePrefix();
        $this->assertIsString($prefix);
    }

    public function testGetTablePrefixWithCustomPrefix(): void
    {
        $adapter = new FrontAccountingDbAdapter('custom_');
        $this->assertEquals('custom_', $adapter->getTablePrefix());
    }

    public function testQueryReturnsArray(): void
    {
        $this->markTestSkipped('FA database functions not available in test environment');
    }

    public function testExecuteDoesNotThrow(): void
    {
        $this->markTestSkipped('FA database functions not available in test environment');
    }
}