<?php

use LiteView\SQL\Crud;

require __DIR__ . '/../vendor/autoload.php';

// 配置方式一
\LiteView\SQL\Config::set('mysql', [
    "driver"   => "mysql",
    "host"     => "qdm722903608.my3w.com",
    "port"     => 3306,
    "username" => "qdm722903608",
    "password" => "7d!5d0af4Den",
    "dbname"   => "qdm722903608_db",
    "charset"  => "utf8mb4",
//
//    "driver"   => "mysql",
//    "host"     => "127.0.0.1",
//    "port"     => 3306,
//    "username" => "test",
//    "password" => "Test.666",
//    "dbname"   => "test",
//    "charset"  => "utf8mb4",
    "prepares" => true
]);

/*
CREATE TABLE `test` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `content` varchar(1000) NOT NULL,
  `addtime` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `pid` int(11) NOT NULL DEFAULT '0' COMMENT '无限级关系：父级ID',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=gbk ROW_FORMAT=DYNAMIC COMMENT='说说';
**/

$db = Crud::db();

// ============================================================
// 辅助函数
// ============================================================
function title(string $text): void
{
    echo "\n=== {$text} ===\n";
}

function dump($var): void
{
    var_dump($var);
}

// ============================================================
// 记录测试前最大 ID，用于最后清理
// ============================================================
$maxIdBeforeTest = (int) $db->select('test', '1', [], 'max(`id`)')->column();

// ============================================================
// 1. 测试 insert：插入根节点（pid = 0）
// ============================================================
title('1. insert root node');
$rootId = $db->insert('test', [
    'content' => 'Root Node Content',
    'addtime' => time(),
    'title'   => 'Root',
    'pid'     => 0,
]);
assert($rootId > 0, 'Root insert failed');
dump("Root ID: {$rootId}");

// ============================================================
// 2. 测试 insert：插入子节点（pid 关联 rootId）
// ============================================================
title('2. insert child node (pid = rootId)');
$childId1 = $db->insert('test', [
    'content' => 'Child 1 Content',
    'addtime' => time(),
    'title'   => 'Child 1',
    'pid'     => $rootId,
]);
assert($childId1 > 0, 'Child1 insert failed');
dump("Child 1 ID: {$childId1}");

$childId2 = $db->insert('test', [
    'content' => 'Child 2 Content',
    'addtime' => time(),
    'title'   => 'Child 2',
    'pid'     => $rootId,
]);
dump("Child 2 ID: {$childId2}");

// ============================================================
// 3. 测试 insert：插入孙节点（pid 关联 childId1）
// ============================================================
title('3. insert grandchild node (pid = childId1)');
$grandchildId = $db->insert('test', [
    'content' => 'Grandchild Content',
    'addtime' => time(),
    'title'   => 'Grandchild',
    'pid'     => $childId1,
]);
assert($grandchildId > 0, 'Grandchild insert failed');
dump("Grandchild ID: {$grandchildId}");

// ============================================================
// 4. 测试 insertAll：批量插入多条测试数据
// ============================================================
title('4. insertAll batch insert');
$batchData = [
    [
        'content' => 'Batch Item 1',
        'addtime' => time(),
        'title'   => 'Batch 1',
        'pid'     => $rootId,
    ],
    [
        'content' => 'Batch Item 2',
        'addtime' => time(),
        'title'   => 'Batch 2',
        'pid'     => $childId1,
    ],
    [
        'content' => 'Batch Item 3',
        'addtime' => time(),
        'title'   => 'Batch 3',
        'pid'     => 0,
    ],
];
$lastId = $db->insertAll('test', $batchData, true);
assert($lastId > 0, 'Batch insert failed');
dump("Batch insert last ID: {$lastId}");

// ============================================================
// 5. 测试 select + one：查询单条记录
// ============================================================
title('5. select -> one()');
$root = $db->select('test', '`id` = ?', [$rootId], '*')->one();
assert(is_array($root) && $root['id'] == $rootId, 'select one failed');
dump($root);

// ============================================================
// 6. 测试 select + all：查询列表（带 limit）
// ============================================================
title('6. select -> all() with limit');
$list = $db->select('test', '`pid` = ?', [$rootId], '*')->all(2);
assert(is_array($list) && count($list) <= 2, 'select all failed');
dump($list);

// ============================================================
// 7. 测试 select + column：统计数量
// ============================================================
title('7. select -> column() count');
$cnt = $db->select('test', '`pid` = ?', [$rootId], 'count(*)')->column();
assert($cnt >= 2, 'select column count failed');
dump("Children count of root: {$cnt}");

// ============================================================
// 8. 测试 select + paginate：分页查询
// ============================================================
title('8. select -> paginate()');
$page = $db->select('test', '1', [], '*')->paginate(3, 'page', 1);
assert(isset($page['paging']) && isset($page['list']), 'paginate failed');
assert($page['paging']['pageSize'] == 3, 'paginate pageSize failed');
dump($page);

// ============================================================
// 9. 测试 select + join 自关联：查询父子关系（pid 关联自身 ID）
// ============================================================
title('9. select + self join (pid references self id)');
$selfJoin = $db
    ->select('test t1', 't1.`id` > 0', [], 't1.`id`, t1.`title`, t1.`pid`, t2.`title` as parent_title')
    ->join([
        ['table' => 'test t2', 'on' => 't1.`pid` = t2.`id`', 'way' => 'left'],
    ])
    ->all(10);
assert(is_array($selfJoin), 'self join failed');
$hasParent = false;
foreach ($selfJoin as $row) {
    if (!empty($row['parent_title'])) {
        $hasParent = true;
        break;
    }
}
assert($hasParent, 'self join parent_title missing');
dump($selfJoin);

// ============================================================
// 10. 测试 select + where 条件：查询某个节点的所有子级
// ============================================================
title('10. select children of a specific node');
$children = $db->select('test', '`pid` = ?', [$rootId], '*')->all();
assert(count($children) >= 2, 'children query failed');
dump($children);

// ============================================================
// 11. 测试 update：更新节点内容
// ============================================================
title('11. update node');
$affected = $db->update('test', [
    'content' => 'Updated Root Content',
    'title'   => 'Updated Root',
], '`id` = ?', [$rootId]);
assert($affected >= 0, 'update failed');
dump("Updated rows: {$affected}");

$updated = $db->select('test', '`id` = ?', [$rootId], '*')->one();
assert($updated['title'] === 'Updated Root', 'update verify failed');
dump($updated);

// ============================================================
// 12. 测试 updateOrInsert：记录已存在 => 更新
// ============================================================
title('12. updateOrInsert (existing record -> update)');
$rst = $db->updateOrInsert('test', ['id' => $rootId], ['content' => 'UpdateOrInsert Updated']);
assert($rst[0] == 1, 'updateOrInsert should update existing record');
dump($rst);

// ============================================================
// 13. 测试 updateOrInsert：记录不存在 => 插入
// ============================================================
title('13. updateOrInsert (non-existing record -> insert)');
$maxId = (int) $db->select('test', '1', [], 'max(`id`)')->column();
$fakeId = $maxId + 9999;
$rst2 = $db->updateOrInsert('test', ['id' => $fakeId], [
    'content' => 'New from updateOrInsert',
    'addtime' => time(),
    'title'   => 'New Node',
    'pid'     => 0,
]);
assert($rst2[0] == 0, 'updateOrInsert should insert new record');
dump($rst2);

// 清理这条新插入的记录
$db->delete('test', '`id` = ?', [$rst2[1]]);

// ============================================================
// 14. 测试 delete：删除单条
// ============================================================
title('14. delete single row');
$tempId = $db->insert('test', [
    'content' => 'To be deleted',
    'addtime' => time(),
    'title'   => 'Temp',
    'pid'     => 0,
]);
$delCnt = $db->delete('test', '`id` = ?', [$tempId]);
assert($delCnt == 1, 'delete single row failed');
dump("Deleted rows: {$delCnt}");

// ============================================================
// 15. 测试 select + order：排序查询
// ============================================================
title('15. select + order()');
$ordered = $db->select('test', '1', [], '*')
    ->order('id', 'desc')
    ->all(5);
assert(is_array($ordered), 'order query failed');
if (count($ordered) >= 2) {
    assert($ordered[0]['id'] >= $ordered[1]['id'], 'order desc failed');
}
dump($ordered);

// ============================================================
// 16. 测试 select + group + having：分组查询
// ============================================================
title('16. select + group() + having()');
$grouped = $db->select('test', '1', [], 'pid, count(*) as cnt')
    ->group('pid')
    ->having('cnt > 0')
    ->all();
assert(is_array($grouped) && count($grouped) > 0, 'group having failed');
dump($grouped);

// ============================================================
// 17. 测试 getRawStatement：查看原始 SQL
// ============================================================
title('17. getRawStatement');
$raw = $db->select('test', '`id` = ? AND `pid` = ?', [$rootId, 0], '*')->getRawStatement(true);
assert(is_string($raw) && str_contains($raw, 'PREPARE'), 'getRawStatement failed');
dump($raw);

// ============================================================
// 18. 测试 ignore insert：重复插入不报错
// ============================================================
title('18. insert ignore');
$ignoreId = $db->insert('test', [
    'id'      => $rootId,
    'content' => 'Ignore Content',
    'addtime' => time(),
    'title'   => 'Ignore',
    'pid'     => 0,
], true);
dump("Insert ignore result (should be 0): {$ignoreId}");

// ============================================================
// 19. 测试 select + all() 不带 limit 参数：获取全部
// ============================================================
title('19. select -> all() without limit');
$allRows = $db->select('test', '`pid` = ?', [$rootId], '*')->all();
assert(is_array($allRows) && count($allRows) >= 3, 'all without limit failed');
dump($allRows);

// ============================================================
// 20. 测试 pid 自关联查询：递归层级（单层，通过 SQL 获取父节点信息）
// ============================================================
title('20. recursive-like self join (parent + child)');
$recursive = $db
    ->select(
        'test c',
        'c.`pid` = ?',
        [$rootId],
        'c.`id`, c.`title`, c.`content`, p.`title` as parent_title, p.`content` as parent_content'
    )
    ->join([
        ['table' => 'test p', 'on' => 'c.`pid` = p.`id`', 'way' => 'left'],
    ])
    ->all();
assert(is_array($recursive), 'recursive self join failed');
foreach ($recursive as $row) {
    assert($row['parent_title'] === 'Updated Root', 'parent info in recursive join incorrect');
}
dump($recursive);

// ============================================================
// 21. 测试 for_update：锁定查询
// ============================================================
title('21. select + for_update()');
$lockSql = $db->select('test', '`id` = ?', [$rootId], '*')
    ->for_update()
    ->getRawStatement(true);
assert(str_contains($lockSql, 'FOR UPDATE'), 'for_update failed');
dump($lockSql);

// ============================================================
// 22. 清理测试数据
// ============================================================
title('22. cleanup test data');
$cleanCnt = $db->delete('test', '`id` > ?', [$maxIdBeforeTest]);
dump("Cleaned rows: {$cleanCnt}");

// ============================================================
// 所有测试完成
// ============================================================
title('All tests passed!');
