<?php

namespace Ksfraser\ModulesDAO\Factory;

use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use Ksfraser\ModulesDAO\Db\FrontAccountingDbAdapter;
use InvalidArgumentException;

class DatabaseAdapterFactory
{
    public static function create(string $driver = 'fa', string $tablePrefix = ''): DbAdapterInterface
    {
        return match ($driver) {
            'fa' => new FrontAccountingDbAdapter($tablePrefix),
            default => throw new InvalidArgumentException("Unknown database driver: {$driver}"),
        };
    }
}