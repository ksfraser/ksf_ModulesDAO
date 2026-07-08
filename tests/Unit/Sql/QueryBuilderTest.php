<?php

namespace Ksfraser\ModulesDAO\Tests\Unit\Sql;

use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use Ksfraser\ModulesDAO\Sql\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class QueryBuilderTest extends TestCase
{
    /** @var DbAdapterInterface&MockObject */
    private $adapter;
    private QueryBuilder $qb;

    protected function setUp(): void
    {
        $this->adapter = $this->createMock(DbAdapterInterface::class);
        $this->adapter->method('getTablePrefix')->willReturn('0_');
        $this->qb = new QueryBuilder($this->adapter);
    }

    public function testBasicSelectAll(): void
    {
        $sql = $this->qb
            ->from('bank_accounts')
            ->toSql();
        $this->assertSame('SELECT * FROM 0_bank_accounts', $sql);
    }

    public function testSelectWithColumns(): void
    {
        $sql = $this->qb
            ->select('id', 'name')
            ->from('bank_accounts')
            ->toSql();
        $this->assertSame('SELECT id, name FROM 0_bank_accounts', $sql);
    }

    public function testAddSelect(): void
    {
        $sql = $this->qb
            ->select('id')
            ->addSelect('name')
            ->from('bank_accounts')
            ->toSql();
        $this->assertSame('SELECT id, name FROM 0_bank_accounts', $sql);
    }

    public function testFromWithAlias(): void
    {
        $sql = $this->qb
            ->from('bank_accounts', 'ba')
            ->toSql();
        $this->assertSame('SELECT * FROM 0_bank_accounts AS ba', $sql);
    }

    public function testFromRaw(): void
    {
        $sql = $this->qb
            ->fromRaw('(SELECT * FROM t) AS sub')
            ->toSql();
        $this->assertSame('SELECT * FROM (SELECT * FROM t) AS sub', $sql);
    }

    public function testWhereEquals(): void
    {
        $sql = $this->qb
            ->from('users')
            ->where('id', 5)
            ->toSql();
        $this->assertSame('SELECT * FROM 0_users WHERE id = ?', $sql);
        $this->assertSame([5], $this->qb->getParams());
    }

    public function testWhereWithExplicitOperator(): void
    {
        $sql = $this->qb
            ->from('users')
            ->where('id', '>', 5)
            ->toSql();
        $this->assertSame('SELECT * FROM 0_users WHERE id > ?', $sql);
        $this->assertSame([5], $this->qb->getParams());
    }

    public function testWhereNotEqual(): void
    {
        $sql = $this->qb
            ->from('users')
            ->where('name', '!=', 'bob')
            ->toSql();
        $this->assertSame('SELECT * FROM 0_users WHERE name != ?', $sql);
        $this->assertSame(['bob'], $this->qb->getParams());
    }

    public function testWhereLike(): void
    {
        $sql = $this->qb
            ->from('users')
            ->where('name', 'LIKE', '%bob%')
            ->toSql();
        $this->assertSame('SELECT * FROM 0_users WHERE name LIKE ?', $sql);
        $this->assertSame(['%bob%'], $this->qb->getParams());
    }

    public function testWhereIn(): void
    {
        $sql = $this->qb
            ->from('users')
            ->where('role', 'IN', ['admin', 'user'])
            ->toSql();
        $this->assertSame('SELECT * FROM 0_users WHERE role IN (?, ?)', $sql);
        $this->assertSame(['admin', 'user'], $this->qb->getParams());
    }

    public function testWhereNotIn(): void
    {
        $sql = $this->qb
            ->from('users')
            ->where('id', 'NOT IN', [1, 2])
            ->toSql();
        $this->assertSame('SELECT * FROM 0_users WHERE id NOT IN (?, ?)', $sql);
        $this->assertSame([1, 2], $this->qb->getParams());
    }

    public function testWhereEmptyInProducesNoRowsClause(): void
    {
        $sql = $this->qb
            ->from('users')
            ->where('id', 'IN', [])
            ->toSql();
        $this->assertSame('SELECT * FROM 0_users WHERE 1=0', $sql);
        $this->assertSame([], $this->qb->getParams());
    }

    public function testWhereEmptyNotInProducesAllRowsClause(): void
    {
        $sql = $this->qb
            ->from('users')
            ->where('id', 'NOT IN', [])
            ->toSql();
        $this->assertSame('SELECT * FROM 0_users WHERE 1=1', $sql);
        $this->assertSame([], $this->qb->getParams());
    }

    public function testWhereBetween(): void
    {
        $sql = $this->qb
            ->from('orders')
            ->where('total', 'BETWEEN', [10, 100])
            ->toSql();
        $this->assertSame('SELECT * FROM 0_orders WHERE total BETWEEN ? AND ?', $sql);
        $this->assertSame([10, 100], $this->qb->getParams());
    }

    public function testWhereNotBetween(): void
    {
        $sql = $this->qb
            ->from('orders')
            ->where('total', 'NOT BETWEEN', [10, 100])
            ->toSql();
        $this->assertSame('SELECT * FROM 0_orders WHERE total NOT BETWEEN ? AND ?', $sql);
        $this->assertSame([10, 100], $this->qb->getParams());
    }

    public function testWhereIsNull(): void
    {
        $sql = $this->qb
            ->from('users')
            ->where('deleted_at', 'IS NULL')
            ->toSql();
        $this->assertSame('SELECT * FROM 0_users WHERE deleted_at IS NULL', $sql);
        $this->assertSame([], $this->qb->getParams());
    }

    public function testWhereIsNotNull(): void
    {
        $sql = $this->qb
            ->from('users')
            ->where('deleted_at', 'IS NOT NULL')
            ->toSql();
        $this->assertSame('SELECT * FROM 0_users WHERE deleted_at IS NOT NULL', $sql);
        $this->assertSame([], $this->qb->getParams());
    }

    public function testWhereNullHelper(): void
    {
        $sql = $this->qb
            ->from('users')
            ->whereNull('deleted_at')
            ->toSql();
        $this->assertSame('SELECT * FROM 0_users WHERE deleted_at IS NULL', $sql);
    }

    public function testWhereNotNullHelper(): void
    {
        $sql = $this->qb
            ->from('users')
            ->whereNotNull('deleted_at')
            ->toSql();
        $this->assertSame('SELECT * FROM 0_users WHERE deleted_at IS NOT NULL', $sql);
    }

    public function testWhereRaw(): void
    {
        $sql = $this->qb
            ->from('users')
            ->where('YEAR(created_at) = 2026', 'RAW')
            ->toSql();
        $this->assertSame('SELECT * FROM 0_users WHERE YEAR(created_at) = 2026', $sql);
        $this->assertSame([], $this->qb->getParams());
    }

    public function testWhereRawWithParams(): void
    {
        $sql = $this->qb
            ->from('users')
            ->where('YEAR(created_at) = ?', 'RAW', [2026])
            ->toSql();
        $this->assertSame('SELECT * FROM 0_users WHERE YEAR(created_at) = ?', $sql);
        $this->assertSame([2026], $this->qb->getParams());
    }

    public function testWhereRawSingleArg(): void
    {
        $sql = $this->qb
            ->from('users')
            ->where('created_at IS NOT NULL')
            ->toSql();
        $this->assertSame('SELECT * FROM 0_users WHERE created_at IS NOT NULL', $sql);
    }

    public function testAndWhere(): void
    {
        $sql = $this->qb
            ->from('users')
            ->where('active', 1)
            ->andWhere('role', 'admin')
            ->toSql();
        $this->assertSame('SELECT * FROM 0_users WHERE active = ? AND role = ?', $sql);
        $this->assertSame([1, 'admin'], $this->qb->getParams());
    }

    public function testOrWhere(): void
    {
        $sql = $this->qb
            ->from('users')
            ->where('role', 'admin')
            ->orWhere('role', 'superadmin')
            ->toSql();
        $this->assertSame('SELECT * FROM 0_users WHERE role = ? OR role = ?', $sql);
        $this->assertSame(['admin', 'superadmin'], $this->qb->getParams());
    }

    public function testOrWhereRaw(): void
    {
        $sql = $this->qb
            ->from('users')
            ->where('active', 1)
            ->orWhere('deleted_at IS NULL')
            ->toSql();
        $this->assertSame('SELECT * FROM 0_users WHERE active = ? OR deleted_at IS NULL', $sql);
        $this->assertSame([1], $this->qb->getParams());
    }

    public function testInnerJoin(): void
    {
        $sql = $this->qb
            ->select('u.*', 'p.name AS profile_name')
            ->from('users', 'u')
            ->join('profiles', 'u.profile_id = p.id', 'p')
            ->toSql();
        $this->assertSame(
            'SELECT u.*, p.name AS profile_name FROM 0_users AS u INNER JOIN 0_profiles AS p ON u.profile_id = p.id',
            $sql
        );
    }

    public function testLeftJoin(): void
    {
        $sql = $this->qb
            ->from('users', 'u')
            ->leftJoin('orders', 'u.id = o.user_id', 'o')
            ->toSql();
        $this->assertSame(
            'SELECT * FROM 0_users AS u LEFT JOIN 0_orders AS o ON u.id = o.user_id',
            $sql
        );
    }

    public function testRightJoin(): void
    {
        $sql = $this->qb
            ->from('users', 'u')
            ->rightJoin('orders', 'u.id = o.user_id', 'o')
            ->toSql();
        $this->assertSame(
            'SELECT * FROM 0_users AS u RIGHT JOIN 0_orders AS o ON u.id = o.user_id',
            $sql
        );
    }

    public function testOrderByAsc(): void
    {
        $sql = $this->qb
            ->from('users')
            ->orderBy('name')
            ->toSql();
        $this->assertSame('SELECT * FROM 0_users ORDER BY name ASC', $sql);
    }

    public function testOrderByDesc(): void
    {
        $sql = $this->qb
            ->from('users')
            ->orderBy('name', 'DESC')
            ->toSql();
        $this->assertSame('SELECT * FROM 0_users ORDER BY name DESC', $sql);
    }

    public function testMultipleOrderBy(): void
    {
        $sql = $this->qb
            ->from('users')
            ->orderBy('last_name')
            ->orderBy('first_name', 'DESC')
            ->toSql();
        $this->assertSame('SELECT * FROM 0_users ORDER BY last_name ASC, first_name DESC', $sql);
    }

    public function testGroupBy(): void
    {
        $sql = $this->qb
            ->select('type', 'COUNT(*) AS cnt')
            ->from('items')
            ->groupBy('type')
            ->toSql();
        $this->assertSame('SELECT type, COUNT(*) AS cnt FROM 0_items GROUP BY type', $sql);
    }

    public function testGroupByHaving(): void
    {
        $sql = $this->qb
            ->select('type', 'COUNT(*) AS cnt')
            ->from('items')
            ->groupBy('type')
            ->having('cnt', '>', 5)
            ->toSql();
        $this->assertSame('SELECT type, COUNT(*) AS cnt FROM 0_items GROUP BY type HAVING cnt > ?', $sql);
        $this->assertSame([5], $this->qb->getParams());
    }

    public function testLimit(): void
    {
        $sql = $this->qb
            ->from('users')
            ->limit(10)
            ->toSql();
        $this->assertSame('SELECT * FROM 0_users LIMIT 10', $sql);
    }

    public function testLimitOffset(): void
    {
        $sql = $this->qb
            ->from('users')
            ->limit(10)
            ->offset(20)
            ->toSql();
        $this->assertSame('SELECT * FROM 0_users LIMIT 20, 10', $sql);
    }

    public function testPage(): void
    {
        $sql = $this->qb
            ->from('users')
            ->page(3, 15)
            ->toSql();
        $this->assertSame('SELECT * FROM 0_users LIMIT 30, 15', $sql);
    }

    public function testGetQueryReturnsBuiltQuery(): void
    {
        $qb = $this->qb
            ->from('users')
            ->where('active', 1);

        $built = $qb->getQuery();

        $this->assertInstanceOf(\Ksfraser\ModulesDAO\Sql\BuiltQuery::class, $built);
        $this->assertSame('SELECT * FROM 0_users WHERE active = ?', $built->getSql());
        $this->assertSame([1], $built->getParams());
    }

    public function testToSqlDoesNotConsumeState(): void
    {
        $qb = $this->qb
            ->from('users')
            ->where('active', 1);

        $sql1 = $qb->toSql();
        $sql2 = $qb->toSql();

        $this->assertSame($sql1, $sql2);
    }

    public function testGetExecutesQuery(): void
    {
        $this->adapter->expects($this->once())
            ->method('query')
            ->with(
                $this->equalTo('SELECT * FROM 0_users WHERE active = ?'),
                $this->equalTo([1])
            )
            ->willReturn([['id' => 1, 'name' => 'Alice']]);

        $rows = $this->qb
            ->from('users')
            ->where('active', 1)
            ->get();

        $this->assertCount(1, $rows);
        $this->assertSame('Alice', $rows[0]['name']);
    }

    public function testOneReturnsFirstRow(): void
    {
        $this->adapter->expects($this->once())
            ->method('query')
            ->with(
                $this->equalTo('SELECT * FROM 0_users LIMIT 1'),
                $this->equalTo([])
            )
            ->willReturn([['id' => 1]]);

        $row = $this->qb
            ->from('users')
            ->one();

        $this->assertNotNull($row);
        $this->assertSame(1, $row['id']);
    }

    public function testOneReturnsNullWhenNoRows(): void
    {
        $this->adapter->method('query')->willReturn([]);

        $row = $this->qb
            ->from('users')
            ->one();

        $this->assertNull($row);
    }

    public function testOneDoesNotMutateOriginal(): void
    {
        $this->adapter->method('query')->willReturn([]);

        $qb = $this->qb->from('users')->where('active', 1);
        $originalSql = $qb->toSql();
        $qb->one();

        $this->assertSame($originalSql, $qb->toSql());
        $this->assertSame('SELECT * FROM 0_users WHERE active = ?', $qb->toSql());
    }

    public function testCount(): void
    {
        $this->adapter->expects($this->once())
            ->method('query')
            ->with(
                $this->equalTo('SELECT COUNT(*) AS cnt FROM 0_users WHERE active = ?'),
                $this->equalTo([1])
            )
            ->willReturn([['cnt' => '42']]);

        $count = $this->qb
            ->from('users')
            ->where('active', 1)
            ->count();

        $this->assertSame(42, $count);
    }

    public function testCountWithoutResults(): void
    {
        $this->adapter->method('query')->willReturn([]);

        $count = $this->qb
            ->from('users')
            ->where('id', 999)
            ->count();

        $this->assertSame(0, $count);
    }

    public function testExistsReturnsTrue(): void
    {
        $this->adapter->method('query')->willReturn([['cnt' => '1']]);

        $this->assertTrue(
            $this->qb->from('users')->where('active', 1)->exists()
        );
    }

    public function testExistsReturnsFalse(): void
    {
        $this->adapter->method('query')->willReturn([['cnt' => '0']]);

        $this->assertFalse(
            $this->qb->from('users')->where('id', 999)->exists()
        );
    }

    public function testInsert(): void
    {
        $this->adapter->expects($this->once())
            ->method('execute')
            ->with(
                $this->equalTo("INSERT INTO 0_users (`name`, `email`) VALUES (?, ?)"),
                $this->equalTo(['Alice', 'alice@test.com'])
            )
            ->willReturn(1);

        $affected = $this->qb
            ->insert('users', ['name' => 'Alice', 'email' => 'alice@test.com'])
            ->execute();

        $this->assertSame(1, $affected);
    }

    public function testUpdate(): void
    {
        $this->adapter->expects($this->once())
            ->method('execute')
            ->with(
                $this->equalTo("UPDATE 0_users SET `name` = ?, `active` = ? WHERE id = ?"),
                $this->equalTo(['Bob', 0, 5])
            )
            ->willReturn(1);

        $affected = $this->qb
            ->update('users', ['name' => 'Bob', 'active' => 0])
            ->where('id', 5)
            ->execute();

        $this->assertSame(1, $affected);
    }

    public function testDelete(): void
    {
        $this->adapter->expects($this->once())
            ->method('execute')
            ->with(
                $this->equalTo('DELETE FROM 0_users WHERE id = ?'),
                $this->equalTo([5])
            )
            ->willReturn(1);

        $affected = $this->qb
            ->delete('users')
            ->where('id', 5)
            ->execute();

        $this->assertSame(1, $affected);
    }

    public function testInsertReturnsNewQueryBuilder(): void
    {
        $insertQb = $this->qb->insert('users', ['name' => 'Alice']);
        $this->assertNotSame($this->qb, $insertQb);
    }

    public function testUpdateReturnsNewQueryBuilder(): void
    {
        $updateQb = $this->qb->update('users', ['name' => 'Bob']);
        $this->assertNotSame($this->qb, $updateQb);
    }

    public function testDeleteReturnsNewQueryBuilder(): void
    {
        $deleteQb = $this->qb->delete('users');
        $this->assertNotSame($this->qb, $deleteQb);
    }

    public function testLastInsertId(): void
    {
        $this->adapter->expects($this->once())
            ->method('lastInsertId')
            ->willReturn(42);

        $id = $this->qb->lastInsertId();
        $this->assertSame(42, $id);
    }

    public function testReset(): void
    {
        $qb = $this->qb
            ->from('users')
            ->where('active', 1)
            ->orderBy('name')
            ->limit(10)
            ->reset()
            ->from('orders');

        $this->assertSame('SELECT * FROM 0_orders', $qb->toSql());
        $this->assertSame([], $qb->getParams());
    }

    public function testChainMultipleWhere(): void
    {
        $sql = $this->qb
            ->from('orders')
            ->where('status', 'paid')
            ->where('total', '>', 100)
            ->where('created_at', '>=', '2026-01-01')
            ->toSql();

        $this->assertSame(
            'SELECT * FROM 0_orders WHERE status = ? AND total > ? AND created_at >= ?',
            $sql
        );
        $this->assertSame(['paid', 100, '2026-01-01'], $this->qb->getParams());
    }

    public function testComplexQuery(): void
    {
        $sql = $this->qb
            ->select('o.id', 'c.name AS customer_name', 'o.total')
            ->from('orders', 'o')
            ->leftJoin('customers', 'o.customer_id = c.id', 'c')
            ->where('o.status', 'paid')
            ->andWhere('o.total', '>=', 50)
            ->orderBy('o.created_at', 'DESC')
            ->limit(20)
            ->offset(10)
            ->toSql();

        $this->assertSame(
            'SELECT o.id, c.name AS customer_name, o.total FROM 0_orders AS o LEFT JOIN 0_customers AS c ON o.customer_id = c.id WHERE o.status = ? AND o.total >= ? ORDER BY o.created_at DESC LIMIT 10, 20',
            $sql
        );
        $this->assertSame(['paid', 50], $this->qb->getParams());
    }

    public function testQueryBuilderReuse(): void
    {
        $qb = $this->qb->from('users');

        $qb->where('active', 1);
        $sql1 = $qb->toSql();

        $qb->where('role', 'admin');
        $sql2 = $qb->toSql();

        $this->assertSame('SELECT * FROM 0_users WHERE active = ?', $sql1);
        $this->assertSame('SELECT * FROM 0_users WHERE active = ? AND role = ?', $sql2);
    }
}
