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

            // 利用 INSERT IGNORE + 唯一索引（或主键）原子性避免 TOCTOU 竞态
            $builder = SentenceFactory::insert($table, array_merge($index, $values), 'ignore');
            $sql     = $builder->build();
            $params  = $builder->getParams();
            $stmt    = $db->prepare($sql, $params);

            if ($stmt->rowCount() > 0) {
                return [0, $db->lastInsertId()];
            }

            if ($values) {
                return [1, $this->update($table, $values, $condition, $prep)];
            }
            return [-1, -1];
        });
    }

    public function insertAll($table, $data, $needLastInsertId = true)
    {
        $builder = SentenceFactory::insert($table, $data);
        $sql     = $builder->build();
        $params  = $builder->getParams();
        $db      = Connect::db($this->key);
        $stmt    = $db->prepare($sql, $params);
        if ($needLastInsertId) {
            return $db->lastInsertId();
        }
        return $stmt->rowCount();
    }

    public function insert($table, $data, $ignore = false)
    {
        $mode    = $ignore ? 'ignore' : 'insert';
        $builder = SentenceFactory::insert($table, $data, $mode);
        $sql     = $builder->build();
        $params  = $builder->getParams();
        $db      = Connect::db($this->key);
        $db->prepare($sql, $params);
        return $db->lastInsertId();
    }

    public function delete($table, $condition, $prep = [])
    {
        $sql = SentenceFactory::delete($table)->where($condition)->build();
        return Connect::db($this->key)->prepare($sql, $prep)->rowCount();
    }

    public function update($table, $data, $condition, $prep = [])
    {
        $builder = SentenceFactory::update($table, $data);
        $sql     = $builder->where($condition)->build();
        $params  = array_merge($builder->getParams(), $prep);
        return Connect::db($this->key)->prepare($sql, $params)->rowCount();
    }

    public function select($table, $condition, $prep = [], $field = '*')
    {
        $builder = SentenceFactory::select($table, $field)->where($condition);
        return new Fetch($builder, $prep, Connect::db($this->key));
    }
}
