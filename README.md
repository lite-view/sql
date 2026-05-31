# LiteView SQL

轻量级 PHP SQL 查询构建器和 CRUD 库，支持参数化查询防止 SQL 注入。

## 安装

```bash
composer require lite-view/sql
```

## 配置

### 方式一：静态配置

```php
use LiteView\SQL\Config;

Config::set('mysql', [
    "driver"   => "mysql",
    "host"     => "127.0.0.1",
    "port"     => 3306,
    "username" => "root",
    "password" => "password",
    "dbname"   => "database",
    "charset"  => "utf8mb4",
    "prepares" => true
]);
```

### 方式二：常量配置

```php
const MYSQL_CONNECTION = [
    'mysql' => [
        "driver"   => "mysql",
        "host"     => "127.0.0.1",
        "port"     => 3306,
        "username" => "root",
        "password" => "password",
        "dbname"   => "database",
        "charset"  => "utf8mb4",
        "prepares" => true
    ]
];
```

## 基本用法

### 插入数据

```php
use LiteView\SQL\Crud;

// 插入单条
$id = Crud::db()->insert('users', [
    'name'  => 'John',
    'email' => 'john@example.com'
]);

// 插入并忽略重复
$id = Crud::db()->insert('users', ['id' => 1, 'name' => 'John'], true);

// 批量插入
$lastId = Crud::db()->insertAll('users', [
    ['name' => 'Alice', 'email' => 'alice@example.com'],
    ['name' => 'Bob',   'email' => 'bob@example.com']
]);
```

### 查询数据

```php
// 查询单条
$user = Crud::db()->select('users', 'id = ?', '*', [1])->one();

// 查询列表
$users = Crud::db()->select('users', 'status = ?', '*', [1])->all();

// 查询列表（带 limit）
$users = Crud::db()->select('users', 'status = ?', '*', [1])->all(10);

// 获取单个值
$count = Crud::db()->select('users', 'status = ?', 'count(*)', [1])->column();
```

### 更新数据

```php
$affected = Crud::db()->update('users', 
    ['name' => 'New Name'], 
    'id = ?', 
    [1]
);
```

### 删除数据

```php
$affected = Crud::db()->delete('users', 'id = ?', [1]);
```

### 更新或插入

```php
// 存在则更新，不存在则插入
[$status, $id] = Crud::db()->updateOrInsert('users', 
    ['id' => 1],           // 唯一索引条件
    ['name' => 'Updated']  // 更新的字段
);
// $status: 0=插入, 1=更新
```

## 高级用法

### 分页查询

```php
$result = Crud::db()->select('users', 'status = ?', '*', [1])->paginate(15);

// 返回结构
[
    'paging' => [
        'total'       => 100,  // 总条数
        'pageSize'    => 15,   // 每页条数
        'currentPage' => 1,    // 当前页
        'pageCount'   => 7,    // 总页数
    ],
    'list' => [...]  // 数据列表
]

// 指定页码
$result = Crud::db()->select('users', '1')->paginate(15, 'page', 2);
```

### JOIN 查询

```php
$orders = Crud::db()
    ->select('users', 'users.id = ?', 'users.name, orders.*', [1])
    ->join([
        ['table' => 'orders', 'on' => 'users.id = orders.user_id', 'way' => 'left']
    ])
    ->all();

// 多表 JOIN
$data = Crud::db()
    ->select('orders', 'orders.id = ?', 'orders.*, users.name, products.title', [1])
    ->join([
        ['table' => 'users',    'on' => 'orders.user_id = users.id'],
        ['table' => 'products', 'on' => 'orders.product_id = products.id', 'way' => 'inner'],
    ])
    ->all();
```

### 排序与分组

```php
// 排序
$users = Crud::db()->select('users', '1')
    ->order('id', 'desc')
    ->all(10);

// 分组 + HAVING
$stats = Crud::db()->select('users', '1', 'status, count(*) as cnt')
    ->group('status')
    ->having('cnt > 5')
    ->all();
```

### 事务

```php
use LiteView\SQL\Connect;

$result = Connect::db()->transaction(function () {
    $db = Crud::db();
    $db->insert('orders', ['user_id' => 1, 'amount' => 100]);
    $db->update('users', ['balance' => 900], 'id = ?', [1]);
    return true;
});
```

### 原生 SQL

```php
// 执行原生查询
$result = Connect::db()->query('SELECT VERSION()')->fetch();

// 原生预处理
$stmt = Connect::db()->prepare('SELECT * FROM users WHERE id = ?', [1]);
$user = $stmt->fetch();
```

### 调试 SQL

```php
// 获取原始 SQL（用于调试）
$sql = Crud::db()->select('users', 'id = ? AND status = ?', '*', [1, 1])
    ->getRawStatement(true);

// 返回可执行的 MySQL PREPARE 语句
echo $sql;
```

## API 参考

### Crud 类

| 方法 | 说明 |
|------|------|
| `db($key = 'mysql')` | 获取 CRUD 实例 |
| `insert($table, $data, $ignore = false)` | 插入数据，返回最后插入 ID |
| `insertAll($table, $data, $needLastInsertId = true)` | 批量插入 |
| `select($table, $condition, $field = '*', $prep = [])` | 查询，返回 Fetch 实例 |
| `update($table, $data, $condition, $prep = [])` | 更新，返回影响行数 |
| `delete($table, $condition, $prep = [])` | 删除，返回影响行数 |
| `updateOrInsert($table, $index, $values = [])` | 更新或插入 |

### Fetch 类

| 方法 | 说明 |
|------|------|
| `one()` | 获取单条记录 |
| `all($limit = null)` | 获取列表 |
| `column($column = 0)` | 获取单个值 |
| `paginate($limit, $pageName, $page)` | 分页查询 |
| `join($tables)` | JOIN 查询 |
| `order($field, $way)` | 排序 |
| `group($field)` | 分组 |
| `having($condition)` | HAVING 条件 |
| `for_update()` | 悲观锁 |
| `getRawStatement($format)` | 获取原始 SQL |

## License

MIT
