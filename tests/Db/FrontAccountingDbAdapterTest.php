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
        $this->markTestSkipped('FAMock not available - requires ksf_famock dependency');
        return new FrontAccountingDbAdapter();
    }

    public function testConstructorWithPrefix(): void
    {
        $adapter = new FrontAccountingDbAdapter('custom_');
        $this->assertInstanceOf(FrontAccountingDbAdapter::class, $adapter);
    }

    public function testGetDialect(): void
    {
        $this->markTestSkipped('FAMock not available');
    }

    public function testGetTablePrefix(): void
    {
        $this->markTestSkipped('FAMock not available');
    }

    public function testGetTablePrefixWithCustomPrefix(): void
    {
        $adapter = new FrontAccountingDbAdapter('custom_');
        $this->assertEquals('custom_', $adapter->getTablePrefix());
    }

    public function testQueryReturnsArray(): void
    {
        $this->markTestSkipped('FAMock not available');
    }

    public function testExecuteDoesNotThrow(): void
    {
        $this->markTestSkipped('FAMock not available');
    }
}