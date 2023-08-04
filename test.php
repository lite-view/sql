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


echo \LiteView\SQL\Sentence\MySQL::select('users m', 'm.id = 1', '*', [], [['table' => 'users u', 'on' => 'm.pid = u.id']]);
echo ';',PHP_EOL;
echo \LiteView\SQL\Sentence\MySQL::select('users m', 'm.id = 1');
echo ';',PHP_EOL;
echo \LiteView\SQL\Sentence\MySQL::delete('users', 'id = 1');
echo ';',PHP_EOL;
echo \LiteView\SQL\Sentence\MySQL::insert('users', ['name' => 'aa', 'num' => null]);
echo ';',PHP_EOL;
echo \LiteView\SQL\Sentence\MySQL::update('users', ['name' => 'bb', 'num' => null], 'id = 20');
echo ';',PHP_EOL;
echo \LiteView\SQL\Sentence\MySQL::update('users', ['name' => 'cc', 'num' => 5], 'id > 0', 1);
echo ';',PHP_EOL;
echo \LiteView\SQL\Sentence\MySQL::update('users', ['name' => 'dd', 'num' => null], 'id > 0', 1, 'id desc');
echo ';',PHP_EOL;
