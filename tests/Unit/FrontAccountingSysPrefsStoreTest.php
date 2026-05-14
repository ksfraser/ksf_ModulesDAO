<?php

declare(strict_types=1);

namespace Ksfraser\ModulesDAO\Tests\Unit;

use Ksfraser\ModulesDAO\Stores\KeyValue\FrontAccountingSysPrefsStore;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class FrontAccountingSysPrefsStoreTest extends TestCase
{
    public function testUnavailableBehaviors(): void
    {
        $store = new FrontAccountingSysPrefsStore();
        self::assertFalse($store->isAvailable());
        self::assertFalse($store->has('a'));
        self::assertSame('d', $store->get('a', 'd'));
        self::assertSame([], $store->all());

        $this->expectException(RuntimeException::class);
        $store->set('a', '1');
    }

    public function testUnavailableHasReturnsFalse(): void
    {
        $store = new FrontAccountingSysPrefsStore();
        $this->assertFalse($store->has('missing'));
    }

    public function testUnavailableGetReturnsDefault(): void
    {
        $store = new FrontAccountingSysPrefsStore();
        $this->assertNull($store->get('missing'));
        $this->assertEquals('default', $store->get('missing', 'default'));
    }

    public function testUnavailableAllReturnsEmptyArray(): void
    {
        $store = new FrontAccountingSysPrefsStore();
        $this->assertEquals([], $store->all());
    }
}