<?php

/**
 * sql 连接与执行
 * PDO::FETCH_ASSOC       //从结果集中获取以列名为索引的关联数组。
 * PDO::FETCH_NUM         //从结果集中获取一个以列在行中的数值偏移量为索引的值数组。
 * PDO::FETCH_BOTH        //这是默认值，包含上面两种数组。
 * PDO::FETCH_OBJ         //从结果集当前行的记录中获取其属性对应各个列名的一个对象。
 * PDO::FETCH_BOUND       //使用fetch()返回TRUE，并将获取的列值赋给在bindParm()方法中指定的相应变量。
 * PDO::FETCH_LAZY        //创建关联数组和索引数组，以及包含列属性的一个对象，从而可以在这三种接口中任选一种。
 *
 * PDO::query() 执行一条SQL语句，如果通过，则返回一个PDOStatement对象。
 * PDO::exec() 执行一条SQL语句，并返回受影响的行数。此函数建议用来进行新增、修改、删除
 * PDOStatement::execute()函数是用于执行已经预处理过的语句，需要配合prepare()函数使用，成功时返回 TRUE， 或者在失败时返回 FALSE
 */

namespace LiteView\SQL;


use PDO;


class Connect
{
    private static $pool;

    public static function db($key = 'mysql')
    {
        if (empty(self::$pool[$key])) {
            Config::init();
            $cfg = Config::$conf[$key];

            $driver = $cfg['driver'] ?? 'mysql';
            $charset = $cfg['charset'] ?? 'utf8mb4';
            $prepares = $cfg['prepares'] ?? false;
            $dsn = "$driver:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['dbname']}";
            $options = [
                // 三种异常模式
                // PDO::ERRMODE_SILENT： 默认模式，不主动报错，需要主动以 $pdo->errorInfo()的形式获取错误信息
                // PDO::ERRMODE_WARNING: 引发 E_WARNING 错误
                // PDO::ERRMODE_EXCEPTION: 主动抛出 exceptions 异常，需要以try{}cath(){}捕获错误信息
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_INIT_COMMAND => "set names $charset",
                PDO::ATTR_EMULATE_PREPARES => $prepares,// 默认为true，本地prepare，execute时发送完整的sql，会把数据库数据由int类型转成string
            ];
            $pdo = new PDO($dsn, $cfg['username'], $cfg['password'], $options);
            self::$pool[$key] = new Cursor($pdo);
        }
        return self::$pool[$key];
    }
}
