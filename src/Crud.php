<?php


namespace LiteView\SQL;


use LiteView\SQL\Sentence\MySQL;

class Crud
{
    private static $key;

    public static function db($key = 'mysql')
    {
        self::$key = $key;
        return new self();
    }

    public function updateOrInsert($table, $index, $values = [])
    {
        $condition = '';
        foreach ($index as $f => $v) {
            $v = addslashes($v);
            $condition .= "`$f` = \"$v\" AND ";
        }
        $condition = substr($condition, 0, -5);
        $exists = Connect::db(Crud::$key)->query("SELECT count(1) as cnt FROM $table WHERE $condition")->fetchColumn();
        if (!$exists) {
            // 会有幻读的重复插入的风险，使用唯一索引可以避免
            return [0, $this->insert($table, array_merge($index, $values), true)];
        }
        if ($values) {
            return [1, $this->update($table, $values, $condition)];
        }
        return [-1, -1];
    }

    public function insertAll($table, $data, $needLastInsertId = true)
    {
        return Connect::db(Crud::$key)->exec(MySQL::insertAll($table, $data), $needLastInsertId);
    }

    public function insert($table, $data, $ignore = false)
    {
        return Connect::db(Crud::$key)->exec(MySQL::insert($table, $data, $ignore), true); //返回插入ID
    }

    public function delete($table, $condition, $prep = [])
    {
        return Connect::db(Crud::$key)->prepare(MySQL::delete($table, $condition), $prep)->rowCount();
    }

    public function update($table, $data, $condition, $prep = [])
    {
        return Connect::db(Crud::$key)->prepare(MySQL::update($table, $data, $condition), $prep)->rowCount();
    }

    public function select($table, $condition, $field = '*', $joins = [])
    {
        return new GOLBuild(Connect::db(Crud::$key), $table, $condition, $field, $joins);
    }
}
