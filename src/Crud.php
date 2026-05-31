<?php


namespace LiteView\SQL;


use LiteView\SQL\Sentence\SentenceFactory;

class Crud
{
    private $key;

    public static function db($key = 'mysql'): Crud
    {
        $instance      = new self();
        $instance->key = $key;
        return $instance;
    }

    public function updateOrInsert($table, $index, $values = [])
    {
        $db = Connect::db($this->key);

        return $db->transaction(function () use ($db, $table, $index, $values) {
            $where = [];
            $prep  = [];
            foreach ($index as $f => $v) {
                $where[] = "`$f` = ?";
                $prep[]  = $v;
            }
            $condition = implode(' AND ', $where);
            $sql       = SentenceFactory::select($table, 'count(1) as cnt')->where($condition)->build();
            $exists    = $db->prepare($sql, $prep)->fetchColumn();
            if (!$exists) {
                // 使用唯一索引可以避免重复插入
                return [0, $this->insert($table, array_merge($index, $values), true)];
            }
            if ($values) {
                return [1, $this->update($table, $values, $condition, $prep)];
            }
            return [-1, -1];
        });
    }

    public function insertAll($table, $data, $needLastInsertId = true)
    {
        $sql = SentenceFactory::insert($table, $data)->build();
        return Connect::db($this->key)->exec($sql, $needLastInsertId);
    }

    public function insert($table, $data, $ignore = false)
    {
        $mode = $ignore ? 'ignore' : 'insert';
        $sql  = SentenceFactory::insert($table, $data, $mode)->build();
        return Connect::db($this->key)->exec($sql, true); //返回插入ID
    }

    public function delete($table, $condition, $prep = [])
    {
        $sql = SentenceFactory::delete($table)->where($condition)->build();
        return Connect::db($this->key)->prepare($sql, $prep)->rowCount();
    }

    public function update($table, $data, $condition, $prep = [])
    {
        $sql = SentenceFactory::update($table, $data)->where($condition)->build();
        return Connect::db($this->key)->prepare($sql, $prep)->rowCount();
    }

    public function select($table, $condition, $field = '*', $joins = [], $prep = [])
    {
        $builder = SentenceFactory::select($table, $field)->where($condition);
        foreach ($joins as $join) {
            $builder->join($join['table'], $join['on'], $join['way'] ?? 'left');
        }
        return new Fetch($builder, $prep, Connect::db($this->key));
    }
}
