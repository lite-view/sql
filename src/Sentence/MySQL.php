<?php


namespace LiteView\SQL\Sentence;


class MySQL
{
    public static function insert($tableName, $data, $ignore = '')
    {
        $fields = '';
        $values = '';
        foreach ($data as $k => $v) {
            $fields .= "`{$k}`,";
            if (is_null($v)) {
                $values .= 'NULL,';
            } else {
                $values .= '"' . addslashes($v) . '",';
            }
        }
        $fields = substr($fields, 0, -1);
        $values = substr($values, 0, -1);
        return "INSERT $ignore INTO $tableName ({$fields}) VALUES({$values})";
    }

    public static function delete($tableName, $condition)
    {
        return "DELETE FROM $tableName WHERE $condition";
    }

    public static function update($tableName, $data, $condition, $limit = null)
    {
        $set = '';
        foreach ($data as $key => $value) {
            $set .= "`$key`=" . '"' . addslashes($value) . '",';
        }
        $set = substr($set, 0, -1);
        $statement = "UPDATE $tableName SET $set WHERE $condition";
        if (!is_null($limit)) {
            $statement .= " LIMIT $limit";
        }
        return $statement;
    }

    public static function select($tableName, $condition, $field = '*', $gol = null, $joins = [])
    {
        //书写顺序：SELECT -> FROM -> JOIN -> ON -> WHERE -> GROUP BY -> HAVING -> UNION -> ORDER BY ->LIMIT
        $join_str = '';
        foreach ($joins as $item) {
            $join_str .= "LEFT JOIN {$item['table']} ON {$item['on']} ";
        }
        $statement = "SELECT $field FROM $tableName $join_str WHERE $condition";
        if (isset($gol['group'])) {
            $statement .= " GROUP BY {$gol['group']}";
        }
        if (isset($gol['order'])) {
            $statement .= " ORDER BY {$gol['order']}";
        }
        if (isset($gol['limit'])) {
            $statement .= " LIMIT {$gol['limit']}";
        }
        return $statement;
    }
}
