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
        $adapter = $this->createAdapter();
        $result = $adapter->query('SELECT * FROM test_table');
        $this->assertIsArray($result);
        $this->assertCount(2, $result); // Mock returns 2 rows
        $this->assertEquals('Test Item', $result[0]['name']);
        $this->assertEquals('Another Item', $result[1]['name']);
    }

    public function testExecuteDoesNotThrow(): void
    {
        $adapter = $this->createAdapter();
        // Should not throw an exception
        $adapter->execute('INSERT INTO test_table (name) VALUES ("test")');
        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    /**
     * Regression: a placeholder whose name is a prefix of another
     * (e.g. :manufacturer_duration inside :manufacturer_duration_unit) must
     * not be corrupted by the shorter replacement. Previously the null value
     * for :manufacturer_duration produced "NULL_unit" in the SQL, breaking
     * warranty saves.
     */
    public function testSubstituteParamsHandlesPrefixCollisions(): void
    {
        $adapter = new FrontAccountingDbAdapter();
        $adapter->execute(
            'INSERT INTO `0_product_warranty` (`manufacturer_duration`, `manufacturer_duration_unit`) '
            . 'VALUES (:manufacturer_duration, :manufacturer_duration_unit)',
            ['manufacturer_duration' => null, 'manufacturer_duration_unit' => 'months']
        );

        $sql = $GLOBALS['__fa_last_sql'];
        // The null duration must become NULL and must NOT corrupt the following
        // _unit placeholder (previously became "NULL_unit").
        $this->assertStringNotContainsString('NULL_unit', $sql);
        $this->assertMatchesRegularExpression(
            '/VALUES\s*\(NULL,\s*[\'"]?months[\'"]?\)/',
            $sql
        );
    }

    public function testSubstituteParamsLeavesUnknownPlaceholdersUntouched(): void
    {
        $adapter = new FrontAccountingDbAdapter();
        $adapter->execute(
            'SELECT * FROM `0_table` WHERE col = :known AND other = :unknown',
            ['known' => 'value']
        );

        $sql = $GLOBALS['__fa_last_sql'];
        $this->assertMatchesRegularExpression('/col\s*=\s*[\'"]?value[\'"]?/', $sql);
        $this->assertStringContainsString('other = :unknown', $sql);
    }
}