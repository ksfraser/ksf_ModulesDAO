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

	/**
	 * @runInSeparateProcess
	 */
	public function testUsesGetCompanyPrefAndSetCompanyPrefWhenPresent(): void
	{
		$this->markTestSkipped('FAMock not available - requires ksf_famock dependency');
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testUsesUpdateCompanyPrefsWhenSetCompanyPrefMissing(): void
	{
		$this->markTestSkipped('FAMock not available - requires ksf_famock dependency');
	}
}
