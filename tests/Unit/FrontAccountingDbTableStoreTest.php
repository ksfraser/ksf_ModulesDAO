<?php

declare(strict_types=1);

namespace Ksfraser\ModulesDAO\Tests\Unit;

use Ksfraser\ModulesDAO\Stores\KeyValue\FrontAccountingDbTableStore;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class FrontAccountingDbTableStoreTest extends TestCase
{
    public function testUnavailableThrowsOnSetAndReturnsDefaults(): void
    {
        $store = new FrontAccountingDbTableStore('prefs');
        self::assertFalse($store->isAvailable());
        self::assertFalse($store->has('a'));
        self::assertSame('d', $store->get('a', 'd'));
        self::assertSame([], $store->all());

        $this->expectException(RuntimeException::class);
        $store->set('a', '1');
    }

    public function testUnavailableHasReturnsFalse(): void
    {
        $store = new FrontAccountingDbTableStore('prefs');
        $this->assertFalse($store->has('missing'));
    }

    public function testUnavailableGetReturnsDefault(): void
    {
        $store = new FrontAccountingDbTableStore('prefs');
        $this->assertNull($store->get('missing'));
        $this->assertEquals('default', $store->get('missing', 'default'));
    }

    public function testUnavailableAllReturnsEmptyArray(): void
    {
        $store = new FrontAccountingDbTableStore('prefs');
        $this->assertEquals([], $store->all());
    }

    public function testUnavailableDeleteDoesNotThrow(): void
    {
        $store = new FrontAccountingDbTableStore('prefs');
        $store->delete('key');
        $this->assertTrue(true);
    }

    public function testConstructorWithCustomColumns(): void
    {
        $store = new FrontAccountingDbTableStore('config', 'name', 'value');
        $this->assertInstanceOf(FrontAccountingDbTableStore::class, $store);
    }
}