<?php

namespace Ksfraser\ModulesDAO\Test\Db;

use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use Ksfraser\ModulesDAO\Db\FrontAccountingDbAdapter;

/**
 * These cases used to live in DbAdapterTestCase.php, which does not match
 * PHPUnit's *Test.php file suffix, so PHPUnit never collected them and every
 * assertion below silently went unrun. They now have a collectable filename.
 */
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

    /**
     * Without a FrontAccounting runtime there is no db_escape(). The adapter
     * must fail closed rather than silently fall back to addslashes(), which is
     * charset-unaware and can be escaped around.
     */
    public function testEscapeFailsClosedWithoutFaRuntime(): void
    {
        if (function_exists('db_escape')) {
            $this->markTestSkipped('db_escape() is defined; run this in a bare process.');
        }

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('db_escape() is unavailable');
        $this->createAdapter()->escape("test'value");
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testEscapeUsesDbEscape(): void
    {
        // Stands in for FA's db_escape() (mysqli_real_escape_string). Real
        // mysqli_real_escape_string also escapes NUL, \n, \r and Ctrl-Z.
        if (!function_exists('db_escape')) {
            eval('function db_escape(string $v): string { return addslashes($v); }');
        }

        $adapter = $this->createAdapter();

        $this->assertEquals("test\\'s value", $adapter->escape("test's value"));
        $this->assertEquals('test\\"value', $adapter->escape('test"value'));
        $this->assertEquals('back\\\\slash', $adapter->escape('back\\slash'));
        $this->assertIsString($adapter->escape("test'value"));
    }
}
