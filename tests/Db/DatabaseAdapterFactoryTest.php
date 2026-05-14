<?php

namespace Ksfraser\ModulesDAO\Test\Db;

use Ksfraser\ModulesDAO\Factory\DatabaseAdapterFactory;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use Ksfraser\ModulesDAO\Db\FrontAccountingDbAdapter;
use PHPUnit\Framework\TestCase;

/**
 * Test for DatabaseAdapterFactory
 */
class DatabaseAdapterFactoryTest extends TestCase
{
    public function testCreateFaAdapter(): void
    {
        $adapter = DatabaseAdapterFactory::create('fa');
        $this->assertInstanceOf(FrontAccountingDbAdapter::class, $adapter);
        $this->assertInstanceOf(DbAdapterInterface::class, $adapter);
    }

    public function testCreateFaAdapterWithPrefix(): void
    {
        $adapter = DatabaseAdapterFactory::create('fa', 'custom_');
        $this->assertInstanceOf(FrontAccountingDbAdapter::class, $adapter);
        $this->assertInstanceOf(DbAdapterInterface::class, $adapter);
        $this->assertEquals('custom_', $adapter->getTablePrefix());
    }

    public function testCreateDefaultAdapter(): void
    {
        $adapter = DatabaseAdapterFactory::create();
        $this->assertInstanceOf(FrontAccountingDbAdapter::class, $adapter);
        $this->assertInstanceOf(DbAdapterInterface::class, $adapter);
    }

    public function testCreateUnknownDriverThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown database driver: unknown');
        DatabaseAdapterFactory::create('unknown');
    }

    public function testFaAdapterMethodsWorkWithoutFa(): void
    {
        $adapter = DatabaseAdapterFactory::create('fa');
        $this->assertEquals('mysql', $adapter->getDialect());
        $this->assertIsString($adapter->getTablePrefix());
        $this->assertStringContainsString("'", $adapter->escape("test'value"));
        $this->assertIsArray($adapter->query('SELECT 1'));
        $this->assertIsInt($adapter->execute('SELECT 1'));
    }
}