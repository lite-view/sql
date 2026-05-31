<?php

require __DIR__ . '/../vendor/autoload.php';

use LiteView\SQL\Sentence\MySQLBuilder;

function assert_eq($expected, $actual, $msg = '')
{
    if ($expected !== $actual) {
        echo "FAIL: {$msg}\n";
        echo "  Expected: {$expected}\n";
        echo "  Actual:   {$actual}\n\n";
        return false;
    }
    echo "PASS: {$msg}\n";
    return true;
}

function assert_throw(callable $fn, $msg = '')
{
    try {
        $fn();
        echo "FAIL: {$msg} (no exception thrown)\n\n";
        return false;
    } catch (\Exception $e) {
        echo "PASS: {$msg} => " . get_class($e) . ": " . $e->getMessage() . "\n";
        return true;
    }
}

$all_pass = true;

// 1. insert 单条（addslashes 会把 ' 转成 \'）
$sql      = MySQLBuilder::insert('users', ['id' => 1, 'name' => "O'Reilly"])->build();
$all_pass = assert_eq(
        'INSERT INTO users (`id`,`name`) VALUES ("1","O\\\'Reilly")',
        $sql,
        'insert single'
    ) && $all_pass;

// 2. insert ignore
$sql      = MySQLBuilder::insert('users', ['id' => 1], 'ignore')->build();
$all_pass = assert_eq(
        'INSERT IGNORE INTO users (`id`) VALUES ("1")',
        $sql,
        'insert ignore'
    ) && $all_pass;

// 3. replace
$sql      = MySQLBuilder::insert('users', ['id' => 1], 'replace')->build();
$all_pass = assert_eq(
        'REPLACE INTO users (`id`) VALUES ("1")',
        $sql,
        'replace into'
    ) && $all_pass;

// 4. insert 批量
$sql      = MySQLBuilder::insert('users', [
    ['id' => 1, 'name' => 'a'],
    ['id' => 2, 'name' => 'b'],
])->build();
$all_pass = assert_eq(
        'INSERT INTO users (`id`,`name`) VALUES ("1","a"),("2","b")',
        $sql,
        'insert batch'
    ) && $all_pass;

// 5. insert 批量缺字段（用 NULL 补）
$sql      = MySQLBuilder::insert('users', [
    ['id' => 1, 'name' => 'a'],
    ['id' => 2],
])->build();
$all_pass = assert_eq(
        'INSERT INTO users (`id`,`name`) VALUES ("1","a"),("2",NULL)',
        $sql,
        'insert batch missing field'
    ) && $all_pass;

// 6. update 基础
$sql      = MySQLBuilder::update('users', ['name' => 'test'])
    ->where('id=1')
    ->build();
$all_pass = assert_eq(
        'UPDATE users SET `name`="test" WHERE id=1',
        $sql,
        'update basic'
    ) && $all_pass;

// 7. update with order & limit
$sql      = MySQLBuilder::update('users', ['status' => 0])
    ->where('age>18')
    ->order('id')
    ->limit(10)
    ->build();
$all_pass = assert_eq(
        'UPDATE users SET `status`="0" WHERE age>18 ORDER BY id desc LIMIT 10',
        $sql,
        'update with order and limit'
    ) && $all_pass;

// 8. delete 基础
$sql      = MySQLBuilder::delete('users')
    ->where('id=1')
    ->build();
$all_pass = assert_eq(
        'DELETE FROM users WHERE id=1',
        $sql,
        'delete basic'
    ) && $all_pass;

// 9. delete with order & limit
$sql      = MySQLBuilder::delete('users')
    ->where('status=0')
    ->order('id', 'asc')
    ->limit(5)
    ->build();
$all_pass = assert_eq(
        'DELETE FROM users WHERE status=0 ORDER BY id asc LIMIT 5',
        $sql,
        'delete with order and limit'
    ) && $all_pass;

// 10. select 基础
$sql      = MySQLBuilder::select('users')->where('id=1')->build();
$all_pass = assert_eq(
        'SELECT * FROM users WHERE id=1',
        $sql,
        'select basic'
    ) && $all_pass;

// 11. select array fields
$sql      = MySQLBuilder::select('users', ['id', 'name', 'email'])->build();
$all_pass = assert_eq(
        'SELECT `id`, `name`, `email` FROM users',
        $sql,
        'select array fields'
    ) && $all_pass;

// 12. select with join（way 保持原始大小写输出）
$sql      = MySQLBuilder::select('users', 'users.*')
    ->join('orders', 'users.id=orders.user_id', 'inner')
    ->where('orders.status=1')
    ->build();
$all_pass = assert_eq(
        'SELECT users.* FROM users inner JOIN orders ON users.id=orders.user_id WHERE orders.status=1',
        $sql,
        'select with join'
    ) && $all_pass;

// 13. select full clause
$sql      = MySQLBuilder::select('users')
    ->where('age>18')
    ->group('role')
    ->having('count(*) > 1')
    ->order('created_at')
    ->limit(10, 20)
    ->for_update()
    ->build();
$all_pass = assert_eq(
        'SELECT * FROM users WHERE age>18 GROUP BY role HAVING count(*) > 1 ORDER BY created_at desc LIMIT 20,10 FOR UPDATE',
        $sql,
        'select full clause'
    ) && $all_pass;

// 14. select 无条件（WHERE 省略）
$sql      = MySQLBuilder::select('users')->build();
$all_pass = assert_eq(
        'SELECT * FROM users',
        $sql,
        'select without where'
    ) && $all_pass;

// 15. where 多次调用 AND 拼接
$sql      = MySQLBuilder::select('users')
    ->where('status=1')
    ->where('age>18')
    ->where('')           // 空值应被忽略
    ->where('deleted=0')
    ->build();
$all_pass = assert_eq(
        'SELECT * FROM users WHERE status=1 AND age>18 AND deleted=0',
        $sql,
        'where chained'
    ) && $all_pass;

// 16. insert NULL 值
$sql      = MySQLBuilder::insert('users', ['id' => 1, 'name' => null])->build();
$all_pass = assert_eq(
        'INSERT INTO users (`id`,`name`) VALUES ("1",NULL)',
        $sql,
        'insert with null'
    ) && $all_pass;

// 17. update NULL 值
$sql      = MySQLBuilder::update('users', ['name' => null])->where('id=1')->build();
$all_pass = assert_eq(
        'UPDATE users SET `name`=NULL WHERE id=1',
        $sql,
        'update with null'
    ) && $all_pass;

// 18. select 多 join（way 保持原始大小写输出）
$sql      = MySQLBuilder::select('users')
    ->join('orders', 'users.id=orders.user_id')
    ->join('items', 'orders.id=items.order_id', 'inner')
    ->where('users.id=1')
    ->build();
$all_pass = assert_eq(
        'SELECT * FROM users left JOIN orders ON users.id=orders.user_id inner JOIN items ON orders.id=items.order_id WHERE users.id=1',
        $sql,
        'select multiple joins'
    ) && $all_pass;

// 19. exception: update without condition
$all_pass = assert_throw(function () {
        MySQLBuilder::update('users', ['name' => 'x'])->build();
    }, 'update without where throws') && $all_pass;

// 20. exception: delete without condition
$all_pass = assert_throw(function () {
        MySQLBuilder::delete('users')->build();
    }, 'delete without where throws') && $all_pass;

// 21. insert empty data
$all_pass = assert_throw(function () {
        MySQLBuilder::insert('users', [])->build();
    }, 'insert empty data throws') && $all_pass;

// 22. exception: update empty data
$all_pass = assert_throw(function () {
        MySQLBuilder::update('users', [])->build();
    }, 'update empty data throws') && $all_pass;

echo "\n====================\n";
if ($all_pass) {
    echo "All tests passed.\n";
    exit(0);
} else {
    echo "Some tests FAILED.\n";
    exit(1);
}
