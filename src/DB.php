<?php


namespace LiteView\SQL;


use LiteView\SQL\Sentence\MySQL;

class DB
{
    private static $key;

    public static function crud($key = 'mysql')
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

        $exists = Connect::db(DB::$key)->query("SELECT count(1) as cnt FROM $table WHERE $condition")->fetchColumn();
        if (!$exists) {
            return $this->insert($table, array_merge($index, $values), true);
        }
        if ($values) {
            return $this->update($table, $values, $condition);
        }
        return 0;
    }

    public function insert($table, $data, $ignore = false)
    {
        $ignore = $ignore ? 'ignore' : '';
        return Connect::db(DB::$key)->exec(MySQL::insert($table, $data, $ignore), true); //返回插入ID
    }
    
    public function delete($table, $condition, $prep = [])
    {
        return Connect::db(DB::$key)->prepare(MySQL::delete($table, $condition), $prep)->rowCount();
    }
    
    public function update($table, $data, $condition, $prep = [])
    {
        return Connect::db(DB::$key)->prepare(MySQL::update($table, $data, $condition), $prep)->rowCount();
    }

    public function select($table, $condition, $field = '*', $prep = [], $gol = [], $joins = [])
    {
        return new Fetch(
            MySQL::select($table, $condition, $field, $gol, $joins),
            $prep,
            Connect::db(DB::$key)
        );
    }
}
