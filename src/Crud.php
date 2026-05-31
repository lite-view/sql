<?php


namespace LiteView\SQL;


use LiteView\SQL\Sentence\SentenceFactory;

class Crud
{
    private static $key;

    public static function db($key = 'mysql'): Crud
    {
        self::$key = $key;
        return new self();
    }

    public function updateOrInsert($table, $index, $values = [])
    {
        $where = [];
        $prep  = [];
        foreach ($index as $f => $v) {
            $where[] = "`$f` = ?";
            $prep[]  = $v;
        }
        $condition = implode(' AND ', $where);
        $sql       = SentenceFactory::select($table, 'count(1) as cnt');
        $exists    = Connect::db(Crud::$key)->prepare($sql, $prep)->fetchColumn();
        if (!$exists) {
            // 会有幻读的重复插入的风险，使用唯一索引可以避免
            return [0, $this->insert($table, array_merge($index, $values), true)];
        }
        if ($values) {
            return [1, $this->update($table, $values, $condition, $prep)];
        }
        return [-1, -1];
    }

    public function insertAll($table, $data, $needLastInsertId = true)
    {
        $sql = SentenceFactory::insert($table, $data);
        return Connect::db(Crud::$key)->exec($sql, $needLastInsertId);
    }

    public function insert($table, $data, $ignore = false)
    {
        $mode = $ignore ? 'ignore' : 'insert';
        $sql  = SentenceFactory::insert($table, $data, $mode);
        return Connect::db(Crud::$key)->exec($sql, true); //返回插入ID
    }

    public function delete($table, $condition, $prep = [])
    {
        $sql = SentenceFactory::delete($table, $condition);
        return Connect::db(Crud::$key)->prepare($sql, $prep)->rowCount();
    }

    public function update($table, $data, $condition, $prep = [])
    {
        $sql = SentenceFactory::update($table, $data, $condition);
        return Connect::db(Crud::$key)->prepare($sql, $prep)->rowCount();
    }

    public function select($table, $condition, $field = '*', $joins = [], $prep = [])
    {
        $sql = SentenceFactory::select($table, $condition, $field, $joins);
        return new Fetch($sql, $prep, Connect::db(Crud::$key));
    }
}
