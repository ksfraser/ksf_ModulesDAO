<?php

namespace Ksfraser\ModulesDAO\Test\Db;

use Ksfraser\ModulesDAO\Db\FrontAccountingDbAdapter;

/**
 * Test for FrontAccountingDbAdapter
 */
class FrontAccountingDbAdapterTest extends DbAdapterTestCase
{
    /**
     * The FA adapter delegates to db_query()/db_escape()/db_insert_id(), which
     * only exist inside a FrontAccounting runtime. Load the famock stubs before
     * any test runs so the adapter has something to talk to. The stub file
     * guards each definition with function_exists(), so loading it when another
     * test already has is harmless.
     */
    public static function setUpBeforeClass(): void
    {
        $candidates = [
            __DIR__ . '/../../../famock/php/FaDbStubs.php',
            __DIR__ . '/../../vendor/ksfraser/famock/php/FaDbStubs.php',
        ];
        foreach ($candidates as $path) {
            if (is_file($path)) {
                require_once $path;
                return;
            }
        }
        throw new \RuntimeException(
            'Could not locate famock FaDbStubs.php; looked in: ' . implode(', ', $candidates)
        );
    }

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

    /**
     * Regression: digit-string ids (e.g. stock_id "0000000") must stay quoted
     * strings. is_numeric() previously emitted them unquoted, so MySQL parsed
     * 0000000 as the integer 0 and the varchar key silently collapsed to "0",
     * making condition assignment writes/reads land on the wrong row.
     */
    public function testSubstituteParamsPreservesLeadingZeroStringIds(): void
    {
        $adapter = new FrontAccountingDbAdapter();
        $adapter->execute(
            'INSERT INTO `0_product_condition_assignments` (stock_id, condition_id)'
            . ' VALUES (:stock_id, :condition_id)',
            ['stock_id' => '0000000', 'condition_id' => 7]
        );

        $sql = $GLOBALS['__fa_last_sql'];
        $this->assertStringContainsString("VALUES ('0000000', 7)", $sql);
    }

    public function testSubstituteParamsQuotesNumericLookingStrings(): void
    {
        $adapter = new FrontAccountingDbAdapter();
        $adapter->query(
            'SELECT * FROM `0_t` WHERE stock_id = :s',
            ['s' => '048492035032']
        );

        $sql = $GLOBALS['__fa_last_sql'];
        $this->assertStringContainsString("stock_id = '048492035032'", $sql);
    }
}