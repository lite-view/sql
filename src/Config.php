<?php


namespace LiteView\SQL;


class Config
{
    public static $conf = null;

    public static function init()
    {
        if (!is_null(self::$conf)) {
            return;
        }
        if (function_exists('cfg')) {
            self::$conf = cfg('database');
        } elseif (defined("MYSQL_CONNECTION")) {
            self::$conf = MYSQL_CONNECTION;
        }
    }

    public static function set($key, $conf)
    {
        self::$conf[$key] = $conf;
    }
}
