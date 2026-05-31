<?php

require __DIR__ . '/../vendor/autoload.php';

use LiteView\SQL\Sentence\MySQLBuilder;

function assert_eq($expected, $actual, $msg = '')
{
    if ($expected !== $actual) {
        echo "FAIL: {$msg}\n";
        echo "  Expected: " . var_export($expected, true) . "\n";
        echo "  Actual:   " . var_export($actual, true) . "\n\n";
        return false;
    }
    echo "PASS: {$msg}\n";
    return true;
}

function assert_params($expected, $actual, $msg = '')
{
    if ($expected !== $actual) {
        echo "FAIL: {$msg}\n";
        echo "  Expected params: " . json_encode($expected) . "\n";
        echo "  Actual params:   " . json_encode($actual) . "\n\n";
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

// ============================================================
// 1. insert 单条 - 参数化查询
// ============================================================
$builder  = MySQLBuilder::insert('users', ['id' => 1, 'name' => "O'Reilly"]);
$sql      = $builder->build();
$all_pass = assert_eq(
        'INSERT INTO users (`id`,`name`) VALUES (?,?)',
        $sql,
        'insert single - sql'
    ) && $all_pass;
$all_pass = assert_params(
        [1, "O'Reilly"],
        $builder->getParams(),
        'insert single - params'
    ) && $all_pass;

// ============================================================
// 2. insert ignore
// ============================================================
$builder  = MySQLBuilder::insert('users', ['id' => 1], 'ignore');
$sql      = $builder->build();
$all_pass = assert_eq(
        'INSERT IGNORE INTO users (`id`) VALUES (?)',
        $sql,
        'insert ignore - sql'
    ) && $all_pass;
$all_pass = assert_params(
        [1],
        $builder->getParams(),
        'insert ignore - params'
    ) && $all_pass;

// ============================================================
// 3. replace
// ============================================================
$builder  = MySQLBuilder::insert('users', ['id' => 1], 'replace');
$sql      = $builder->build();
$all_pass = assert_eq(
        'REPLACE INTO users (`id`) VALUES (?)',
        $sql,
        'replace into - sql'
    ) && $all_pass;
$all_pass = assert_params(
        [1],
        $builder->getParams(),
        'replace into - params'
    ) && $all_pass;

// ============================================================
// 4. insert 批量
// ============================================================
$builder  = MySQLBuilder::insert('users', [
    ['id' => 1, 'name' => 'a'],
    ['id' => 2, 'name' => 'b'],
]);
$sql      = $builder->build();
$all_pass = assert_eq(
        'INSERT INTO users (`id`,`name`) VALUES (?,?),(?,?)',
        $sql,
        'insert batch - sql'
    ) && $all_pass;
$all_pass = assert_params(
        [1, 'a', 2, 'b'],
        $builder->getParams(),
        'insert batch - params'
    ) && $all_pass;

// ============================================================
// 5. insert 批量缺字段（用 NULL 补）
// ============================================================
$builder  = MySQLBuilder::insert('users', [
    ['id' => 1, 'name' => 'a'],
    ['id' => 2],
]);
$sql      = $builder->build();
$all_pass = assert_eq(
        'INSERT INTO users (`id`,`name`) VALUES (?,?),(?,NULL)',
        $sql,
        'insert batch missing field - sql'
    ) && $all_pass;
$all_pass = assert_params(
        [1, 'a', 2],
        $builder->getParams(),
        'insert batch missing field - params'
    ) && $all_pass;

// ============================================================
// 6. update 基础
// ============================================================
$builder  = MySQLBuilder::update('users', ['name' => 'test'])
    ->where('id=?');
$sql      = $builder->build();
$all_pass = assert_eq(
        'UPDATE users SET `name`=? WHERE id=?',
        $sql,
        'update basic - sql'
    ) && $all_pass;
$all_pass = assert_params(
        ['test'],
        $builder->getParams(),
        'update basic - params'
    ) && $all_pass;

// ============================================================
// 7. update with order & limit
// ============================================================
$builder  = MySQLBuilder::update('users', ['status' => 0])
    ->where('age>?')
    ->order('id')
    ->limit(10);
$sql      = $builder->build();
$all_pass = assert_eq(
        'UPDATE users SET `status`=? WHERE age>? ORDER BY id desc LIMIT 10',
        $sql,
        'update with order and limit - sql'
    ) && $all_pass;
$all_pass = assert_params(
        [0],
        $builder->getParams(),
        'update with order and limit - params'
    ) && $all_pass;

// ============================================================
// 8. delete 基础
// ============================================================
$builder  = MySQLBuilder::delete('users')
    ->where('id=?');
$sql      = $builder->build();
$all_pass = assert_eq(
        'DELETE FROM users WHERE id=?',
        $sql,
        'delete basic - sql'
    ) && $all_pass;

// ============================================================
// 9. delete with order & limit
// ============================================================
$builder  = MySQLBuilder::delete('users')
    ->where('status=?')
    ->order('id', 'asc')
    ->limit(5);
$sql      = $builder->build();
$all_pass = assert_eq(
        'DELETE FROM users WHERE status=? ORDER BY id asc LIMIT 5',
        $sql,
        'delete with order and limit - sql'
    ) && $all_pass;

// ============================================================
// 10. select 基础
// ============================================================
$builder  = MySQLBuilder::select('users')->where('id=?');
$sql      = $builder->build();
$all_pass = assert_eq(
        'SELECT * FROM users WHERE id=?',
        $sql,
        'select basic - sql'
    ) && $all_pass;

// ============================================================
// 11. select array fields
// ============================================================
$sql      = MySQLBuilder::select('users', ['id', 'name', 'email'])->build();
$all_pass = assert_eq(
        'SELECT `id`, `name`, `email` FROM users',
        $sql,
        'select array fields'
    ) && $all_pass;

// ============================================================
// 12. select with join
// ============================================================
$builder  = MySQLBuilder::select('users', 'users.*')
    ->join('orders', 'users.id=orders.user_id', 'inner')
    ->where('orders.status=?');
$sql      = $builder->build();
$all_pass = assert_eq(
        'SELECT users.* FROM users inner JOIN orders ON users.id=orders.user_id WHERE orders.status=?',
        $sql,
        'select with join - sql'
    ) && $all_pass;

// ============================================================
// 13. select full clause
// ============================================================
$builder  = MySQLBuilder::select('users')
    ->where('age>?')
    ->group('role')
    ->having('count(*) > 1')
    ->order('created_at')
    ->limit(10, 20)
    ->for_update();
$sql      = $builder->build();
$all_pass = assert_eq(
        'SELECT * FROM users WHERE age>? GROUP BY role HAVING count(*) > 1 ORDER BY created_at desc LIMIT 20,10 FOR UPDATE',
        $sql,
        'select full clause - sql'
    ) && $all_pass;

// ============================================================
// 14. select 无条件（WHERE 省略）
// ============================================================
$sql      = MySQLBuilder::select('users')->build();
$all_pass = assert_eq(
        'SELECT * FROM users',
        $sql,
        'select without where'
    ) && $all_pass;

// ============================================================
// 15. where 多次调用 AND 拼接
// ============================================================
$builder  = MySQLBuilder::select('users')->where('deleted=?');
$sql      = $builder->build();
$all_pass = assert_eq(
        'SELECT * FROM users WHERE deleted=?',
        $sql,
        'where chained - sql'
    ) && $all_pass;

// ============================================================
// 16. insert NULL 值
// ============================================================
$builder  = MySQLBuilder::insert('users', ['id' => 1, 'name' => null]);
$sql      = $builder->build();
$all_pass = assert_eq(
        'INSERT INTO users (`id`,`name`) VALUES (?,NULL)',
        $sql,
        'insert with null - sql'
    ) && $all_pass;
$all_pass = assert_params(
        [1],
        $builder->getParams(),
        'insert with null - params'
    ) && $all_pass;

// ============================================================
// 17. update NULL 值
// ============================================================
$builder  = MySQLBuilder::update('users', ['name' => null])->where('id=?');
$sql      = $builder->build();
$all_pass = assert_eq(
        'UPDATE users SET `name`=NULL WHERE id=?',
        $sql,
        'update with null - sql'
    ) && $all_pass;
$all_pass = assert_params(
        [],
        $builder->getParams(),
        'update with null - params'
    ) && $all_pass;

// ============================================================
// 18. select 多 join
// ============================================================
$builder  = MySQLBuilder::select('users')
    ->join('orders', 'users.id=orders.user_id')
    ->join('items', 'orders.id=items.order_id', 'inner')
    ->where('users.id=?');
$sql      = $builder->build();
$all_pass = assert_eq(
        'SELECT * FROM users left JOIN orders ON users.id=orders.user_id inner JOIN items ON orders.id=items.order_id WHERE users.id=?',
        $sql,
        'select multiple joins - sql'
    ) && $all_pass;

// ============================================================
// 19. count 基础
// ============================================================
$builder  = MySQLBuilder::select('users')->where('status=?');
$sql      = $builder->count();
$all_pass = assert_eq(
        'SELECT count(*) FROM users WHERE status=?',
        $sql,
        'count basic - sql'
    ) && $all_pass;

// ============================================================
// 20. count with join
// ============================================================
$builder  = MySQLBuilder::select('users')
    ->join('orders', 'users.id=orders.user_id')
    ->where('orders.status=?');
$sql      = $builder->count();
$all_pass = assert_eq(
        'SELECT count(*) FROM users left JOIN orders ON users.id=orders.user_id WHERE orders.status=?',
        $sql,
        'count with join - sql'
    ) && $all_pass;

// ============================================================
// 21. exception: update without condition
// ============================================================
$all_pass = assert_throw(function () {
        MySQLBuilder::update('users', ['name' => 'x'])->build();
    }, 'update without where throws') && $all_pass;

// ============================================================
// 22. exception: delete without condition
// ============================================================
$all_pass = assert_throw(function () {
        MySQLBuilder::delete('users')->build();
    }, 'delete without where throws') && $all_pass;

// ============================================================
// 23. insert empty data
// ============================================================
$all_pass = assert_throw(function () {
        MySQLBuilder::insert('users', [])->build();
    }, 'insert empty data throws') && $all_pass;

// ============================================================
// 24. exception: update empty data
// ============================================================
$all_pass = assert_throw(function () {
        MySQLBuilder::update('users', [])->build();
    }, 'update empty data throws') && $all_pass;

// ============================================================
// 25. exception: invalid insert mode
// ============================================================
$all_pass = assert_throw(function () {
        MySQLBuilder::insert('users', ['id' => 1], 'invalid_mode')->build();
    }, 'invalid insert mode throws') && $all_pass;

// ============================================================
// 26. batch insert empty row
// ============================================================
$all_pass = assert_throw(function () {
        MySQLBuilder::insert('users', [[]])->build();
    }, 'insert empty batch row throws') && $all_pass;

// ============================================================
// 27. limit with null number
// ============================================================
$builder  = MySQLBuilder::select('users')->limit(null);
$sql      = $builder->build();
$all_pass = assert_eq(
        'SELECT * FROM users',
        $sql,
        'limit null number'
    ) && $all_pass;

// ============================================================
// 28. limit with zero offset
// ============================================================
$builder  = MySQLBuilder::select('users')->limit(10, 0);
$sql      = $builder->build();
$all_pass = assert_eq(
        'SELECT * FROM users LIMIT 10',
        $sql,
        'limit zero offset'
    ) && $all_pass;

echo "\n====================\n";
if ($all_pass) {
    echo "All 28 tests passed.\n";
    exit(0);
} else {
    echo "Some tests FAILED.\n";
    exit(1);
}
