<?php


namespace LiteView\SQL\Sentence;


class MySQL
{
    public static function insert($tableName, $data, $ignore = ''): string
    {
        $fields = '';
        $values = '';
        foreach ($data as $key => $value) {
            $fields .= "`$key`,";
            if (is_null($value)) {
                $values .= 'NULL,';
            } else {
                $value = addslashes($value);
                $values .= "\"$value\",";
            }
        }
        $fields = substr($fields, 0, -1);
        $values = substr($values, 0, -1);
        return "INSERT $ignore INTO $tableName ($fields) VALUES($values)";
    }

    public static function delete($tableName, $condition): string
    {
        return "DELETE FROM $tableName WHERE $condition";
    }

    public static function update($tableName, $data, $condition, $limit = null, $order = null): string
    {
        $set = '';
        foreach ($data as $key => $value) {
            if (is_null($value)) {
                $set .= "`$key`=NULL,";
            } else {
                $value = addslashes($value);
                $set .= "`$key`=\"$value\",";
            }
        }
        $set = substr($set, 0, -1);
        $statement = "UPDATE $tableName SET $set WHERE $condition";
        if (!is_null($order)) {
            $statement .= " ORDER BY $order";
        }
        if (!is_null($limit)) {
            $statement .= " LIMIT $limit";
        }
        return $statement;
    }

    public static function select($tableName, $condition, $field = '*', $gol = [], $joins = []): string
    {
        //书写顺序：SELECT -> FROM -> JOIN -> ON -> WHERE -> GROUP BY -> HAVING -> UNION -> ORDER BY -> LIMIT
        $leftJoin = '';
        foreach ($joins as $item) {
            $leftJoin .= "LEFT JOIN {$item['table']} ON {$item['on']} ";
        }
        $statement = "SELECT $field FROM $tableName $leftJoin WHERE $condition";
        if (isset($gol['group'])) {
            $statement .= " GROUP BY {$gol['group']}";
        }
        if (isset($gol['having'])) {
            $statement .= " HAVING {$gol['having']}";
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
