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
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=gbk ROW_FORMAT=DYNAMIC COMMENT='说说';
**/

$r = Crud::db()->select('test', '1')->paginate(3);
var_dump($r);