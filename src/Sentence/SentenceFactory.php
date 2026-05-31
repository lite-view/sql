<?php

namespace LiteView\SQL\Sentence;

class SentenceFactory
{
    private static $driver = 'mysql';

    public static function insert($table, $data, $mode = 'insert'): string
    {
        if (self::$driver === 'mysql') {
            return MySQLBuilder::insert($table, $data, $mode)->build();
        }
        throw new \Exception('Unsupported driver');
    }

    public static function update($table, $data, $condition): string
    {
        if (self::$driver === 'mysql') {
            return MySQLBuilder::update($table, $data)->where($condition)->build();
        }
        throw new \Exception('Unsupported driver');
    }

    public static function select($table, $fields = '*'): string
    {
        if (self::$driver === 'mysql') {
            return MySQLBuilder::select($table, $fields)->build();
        }
        throw new \Exception('Unsupported driver');
    }

    public static function delete($table, $condition): string
    {
        if (self::$driver === 'mysql') {
            return MySQLBuilder::delete($table)->where($condition)->build();
        }

        throw new \Exception('Unsupported driver');
    }
}