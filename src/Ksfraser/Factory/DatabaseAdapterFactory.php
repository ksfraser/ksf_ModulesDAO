<?php

namespace Ksfraser\ModulesDAO\Factory;

use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use Ksfraser\ModulesDAO\Db\FrontAccountingDbAdapter;
use InvalidArgumentException;

class DatabaseAdapterFactory
{
    public static function create(string $driver = 'fa', string $tablePrefix = ''): DbAdapterInterface
    {
        // switch, not match: match() is PHP 8.0+ and the cross-module floor is
        // PHP 7.3 (AGENTS.md section 1; prod runs 7.3 on Fedora 30). On 7.3 this
        // file is a hard parse error, so merely autoloading the factory would
        // take the calling page down.
        switch (strtolower($driver)) {
            case 'fa':
            case 'frontaccounting':
                return new FrontAccountingDbAdapter($tablePrefix);
            default:
                throw new InvalidArgumentException("Unknown database driver: {$driver}");
        }
    }
}