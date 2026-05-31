<?php

namespace LiteView\SQL\Sentence;

class SentenceFactory
{
    private static $driver = 'mysql';

    public static function setDriver($driver)
    {
        self::$driver = $driver;
    }

    public static function insert($table, $data, $mode = 'insert')
    {
        if (self::$driver === 'mysql') {
            return MySQLBuilder::insert($table, $data, $mode);
        }
        throw new \Exception('Unsupported driver');
    }

    public static function update($table, $data)
    {
        if (self::$driver === 'mysql') {
            return MySQLBuilder::update($table, $data);
        }
        throw new \Exception('Unsupported driver');
    }

    public static function select($table, $fields = '*')
    {
        if (self::$driver === 'mysql') {
            return MySQLBuilder::select($table, $fields);
        }
        throw new \Exception('Unsupported driver');
    }

    public static function delete($table)
    {
        if (self::$driver === 'mysql') {
            return MySQLBuilder::delete($table);
        }
        throw new \Exception('Unsupported driver');
    }
}