# sql

```
<?php


use LiteView\SQL\Crud;


require __DIR__ . '/vendor/autoload.php';

// 配置方式一
\LiteView\SQL\Config::set('mysql', [
    "driver" => "mysql",
    "host" => "127.0.0.1",
    "port" => 3306,
    "username" => "test",
    "password" => "Test.666",
    "dbname" => "test",
    "charset" => "utf8mb4",
    "prepares" => true
]);


// 配置方式二
//const MYSQL_CONNECTION = [
//    'mysql' => [
//        "driver" => "mysql",
//        "host" => "127.0.0.1",
//        "port" => 3306,
//        "username" => "test",
//        "password" => "Test.666",
//        "dbname" => "test",
//        "charset" => "utf8mb4",
//        "prepares" => true
//    ]
//];


$r = \LiteView\SQL\Connect::db()->query('select version()')->fetch();
print_r($r);
$r = Crud::db()->updateOrInsert('users', ['id' => 11]);
print_r($r);
$r = Crud::db()->update('users', ['name' => time()], 'id = 11');
print_r($r);
$r = Crud::db()->insert('users', ['name' => 'xxx']);
print_r($r);
//
$r = Crud::db()->select('users', '1')->prep()->all();
print_r($r);
$r = Crud::db()->select('users', '1')->prep()->one();
print_r($r);
$r = Crud::db()->select('users', '1')->prep()->paginate(10);
print_r($r);

```
