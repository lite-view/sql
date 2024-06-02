<?php


namespace LiteView\SQL\Sentence;


class MySQL
{
    public static function insertAll($tableName, $data)
    {
        $fields = '';
        foreach ($data[0] as $key => $nil) {
            $fields .= "`$key`,";
        }
        $fields = substr($fields, 0, -1);
        $sentence = "INSERT INTO $tableName ($fields) VALUES ";
        foreach ($data as $row) {
            $values = '';
            foreach ($row as $value) {
                if (is_null($value)) {
                    $values .= 'NULL,';
                } else {
                    $value = addslashes($value);
                    $values .= "\"$value\",";
                }
            }
            $values = substr($values, 0, -1);
            $sentence .= "($values),";
        }
        return substr($sentence, 0, -1);
    }

    public static function insert($tableName, $data, $ignore = false): string
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
        $ignore = $ignore ? 'IGNORE' : '';
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
        $sentence = "UPDATE $tableName SET $set WHERE $condition";
        if (!is_null($order)) {
            $sentence .= " ORDER BY $order";
        }
        if (!is_null($limit)) {
            $sentence .= " LIMIT $limit";
        }
        return $sentence;
    }

    public static function select($tableName, $condition, $field = '*', $gol = [], $joins = []): string
    {
        //书写顺序：SELECT -> FROM -> JOIN -> ON -> WHERE -> GROUP BY -> HAVING -> UNION -> ORDER BY -> LIMIT -> FOR UPDATE
        $leftJoin = '';
        foreach ($joins as $item) {
            $leftJoin .= "LEFT JOIN {$item['table']} ON {$item['on']} ";
        }
        $sentence = "SELECT $field FROM $tableName $leftJoin WHERE $condition";
        if (isset($gol['group'])) {
            $sentence .= " GROUP BY {$gol['group']}";
        }
        if (isset($gol['having'])) {
            $sentence .= " HAVING {$gol['having']}";
        }
        if (isset($gol['order'])) {
            $sentence .= " ORDER BY {$gol['order']}";
        }
        if (isset($gol['limit'])) {
            $sentence .= " LIMIT {$gol['limit']}";
        }
        if (isset($gol['fu'])) {
            $sentence .= " {$gol['fu']}";
        }
        return $sentence;
    }
}
