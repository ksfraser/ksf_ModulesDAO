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

	/**
	 * @runInSeparateProcess
	 */
	public function testCrudWithStubbedFaDb(): void
	{
		$this->markTestSkipped('FAMock not available - requires ksf_famock dependency');
	}
}
