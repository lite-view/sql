<?php


use LiteView\SQL\DB;

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


$r = DB::cursor()->query('select version()')->fetch();
print_r($r);
$r = DB::crud()->select('users', '1')->all();
print_r($r);
$r = DB::crud()->select('users', '1')->one();
print_r($r);
$r = DB::crud()->select('users', '1')->paginate(10);
print_r($r);
$r = DB::crud()->updateOrInsert('users', ['id' => 11]);
print_r($r);
$r = DB::crud()->update('users', ['name' => time()], 'id = 11');
print_r($r);
$r = DB::crud()->insert('users', ['name' => 'xxx']);
print_r($r);
