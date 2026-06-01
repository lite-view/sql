<?php

require __DIR__ . '/../vendor/autoload.php';

use LiteView\SQL\Sentence\MySQLBuilder;

function test($label, $cond, $expected)
{
    $b   = MySQLBuilder::select('t')->where($cond);
    $sql = $b->build();
    if ($sql === $expected) {
        echo "PASS: {$label}\n";
    } else {
        echo "FAIL: {$label}\n  expected: {$expected}\n  got:      {$sql}\n";
    }
}

// 1. string (backward compat)
test('string', 'id=1', 'SELECT * FROM t WHERE id=1');

// 2. hash simple
test('hash simple', ['id' => 1], 'SELECT * FROM t WHERE id=1');

// 3. hash multi
test('hash multi', ['id' => 1, 'name' => 'xx'], "SELECT * FROM t WHERE id=1 AND name='xx'");

// 4. hash with ?
test('hash with ?', ['id' => '?', 'status' => '?'], 'SELECT * FROM t WHERE id=? AND status=?');

// 5. hash with null
test('hash with null', ['pid' => null], 'SELECT * FROM t WHERE pid=NULL');

// 6. hash with bool
test('hash with bool', ['active' => true, 'deleted' => false], 'SELECT * FROM t WHERE active=1 AND deleted=0');

// 7. hash IN
test('hash IN', ['id' => [1, 2, 3]], 'SELECT * FROM t WHERE id IN (1,2,3)');

// 8. hash IN with ?
test('hash IN with ?', ['id' => ['?', '?']], 'SELECT * FROM t WHERE id IN (?,?)');

// 9. indexed list (no operator)
test('indexed list', ['id=1', 'status=2'], 'SELECT * FROM t WHERE (id=1) AND (status=2)');

// 10. or operator (strings)
test('or operator', ['or', 'a=1', 'b=2'], 'SELECT * FROM t WHERE (a=1) OR (b=2)');

// 11. or nested
test('or nested', ['or', ['id' => 1], ['name' => 'xx']], "SELECT * FROM t WHERE (id=1) OR (name='xx')");

// 12. in operator
test('in operator', ['in', 'id', [1, 2, 3]], 'SELECT * FROM t WHERE id IN (1,2,3)');

// 13. not in operator
test('not in operator', ['not in', 'id', [1, 2]], 'SELECT * FROM t WHERE id NOT IN (1,2)');

// 14. between
test('between', ['between', 'age', 10, 20], 'SELECT * FROM t WHERE age BETWEEN 10 AND 20');

// 15. like
test('like', ['like', 'name', '%test%'], "SELECT * FROM t WHERE name LIKE '%test%'");

// 16. comparison >
test('comparison >', ['>', 'age', 18], 'SELECT * FROM t WHERE age > 18');

// 17. comparison <=
test('comparison <=', ['<=', 'age', 30], 'SELECT * FROM t WHERE age <= 30');

// 18. exists
test('exists', ['exists', 'SELECT 1 FROM u WHERE u.id=t.id'], 'SELECT * FROM t WHERE EXISTS (SELECT 1 FROM u WHERE u.id=t.id)');

// 19. nested and/or
test('nested and/or', ['and', ['or', 'a=1', 'b=2'], ['status' => 1]], "SELECT * FROM t WHERE ((a=1) OR (b=2)) AND (status=1)");

// 20. deep nested
test('deep nested', ['or', ['and', ['id' => 1], ['status' => 'active']], ['id' => 2]], "SELECT * FROM t WHERE ((id=1) AND (status='active')) OR (id=2)");

// 21. not between
test('not between', ['not between', 'score', 60, 100], 'SELECT * FROM t WHERE score NOT BETWEEN 60 AND 100');

// 22. not like
test('not like', ['not like', 'name', '%spam%'], "SELECT * FROM t WHERE name NOT LIKE '%spam%'");

// 23. not exists
test('not exists', ['not exists', 'SELECT 1'], 'SELECT * FROM t WHERE NOT EXISTS (SELECT 1)');

// 24. empty IN array
test('empty IN', ['in', 'id', []], 'SELECT * FROM t WHERE 1=0');

// 25. empty hash IN
test('empty hash IN', ['id' => []], 'SELECT * FROM t WHERE 1=0');

// 26. mixed indexed list
test('mixed indexed', ['a=1', ['or', 'b=2', 'c=3']], 'SELECT * FROM t WHERE (a=1) AND ((b=2) OR (c=3))');

// 27. IN with string subquery
test('in subquery', ['in', 'id', 'SELECT id FROM u'], 'SELECT * FROM t WHERE id IN (SELECT id FROM u)');

// 28. update with array where
$b   = MySQLBuilder::update('t', ['name' => 'new'])->where(['id' => 1]);
$sql = $b->build();
if ($sql === 'UPDATE t SET `name`=? WHERE id=1') {
    echo "PASS: update with array\n";
} else {
    echo "FAIL: update with array\n  expected: UPDATE t SET `name`=? WHERE id=1\n  got:      {$sql}\n";
}
