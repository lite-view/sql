<?php

namespace LiteView\SQL\Sentence;
class MySQLBuilder
{
    private $table = null;
    private $fields = null;
    private $intent = null; // insert,select,update,delete
    private $data = null;
    private $joins = [];
    private $group_by = null;
    private $order_by = [];
    private $limit_offset = null;
    private $having_condition = null;
    private $is_for_update = false;

    private $condition = null;
    private $params = [];

    public static function insert($table, $data, $mode = 'insert'): MySQLBuilder
    {
        $_map = [
            'insert'  => 'INSERT INTO',
            'ignore'  => 'INSERT IGNORE INTO',
            'replace' => 'REPLACE INTO',
        ];

        if (!isset($_map[$mode])) {
            throw new \InvalidArgumentException("Invalid insert mode: {$mode}");
        }

        $builder         = new MySQLBuilder();
        $builder->table  = $table;
        $builder->intent = $_map[$mode];
        $builder->data   = $data;
        return $builder;
    }

    public static function update($table, $data): MySQLBuilder
    {
        $builder         = new MySQLBuilder();
        $builder->table  = $table;
        $builder->intent = 'update';
        $builder->data   = $data;
        return $builder;
    }

    public static function select($table, $fields = '*'): MySQLBuilder
    {
        $builder         = new MySQLBuilder();
        $builder->table  = $table;
        $builder->fields = $fields;
        $builder->intent = 'select';
        return $builder;
    }

    public static function delete($table): MySQLBuilder
    {
        $builder         = new MySQLBuilder();
        $builder->table  = $table;
        $builder->intent = 'delete';
        return $builder;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function join($table, $on, $way = 'left'): MySQLBuilder
    {
        $this->joins[] = [
            'table' => $table,
            'on'    => $on,
            'way'   => $way,
        ];
        return $this;
    }

    public function where($condition): MySQLBuilder
    {
        if ($condition === null || $condition === '') {
            throw new \Exception("Condition can't be empty");
        }

        if (is_array($condition)) {
            $this->condition = WhereParser::build($condition);
        } else {
            $this->condition = $condition;
        }
        return $this;
    }

    public function group($field): MySQLBuilder
    {
        $this->group_by = $field;
        return $this;
    }

    public function having($condition): MySQLBuilder
    {
        $this->having_condition = $condition;
        return $this;
    }

    public function order($field, $way = 'desc'): MySQLBuilder
    {
        $this->order_by[] = "{$field} {$way}";
        return $this;
    }

    public function limit($number, $offset = 0): MySQLBuilder
    {
        if ($number !== null) {
            if ($offset > 0) {
                $this->limit_offset = "{$offset},{$number}";
            } else {
                $this->limit_offset = "{$number}";
            }
        }
        return $this;
    }

    public function for_update(): MySQLBuilder
    {
        $this->is_for_update = true;
        return $this;
    }

    public function count($fields = '*'): string
    {
        $sql = ['SELECT', "count($fields)", 'FROM', $this->table];
        if ($this->joins) {
            $join_str = '';
            foreach ($this->joins as $item) {
                $join_str .= "{$item['way']} JOIN {$item['table']} ON {$item['on']} ";
            }
            $sql[] = trim($join_str);
        }
        $sql = array_merge($sql, ['WHERE', $this->condition]);
        if ($this->is_for_update) {
            $sql[] = "FOR UPDATE";
        }
        return implode(' ', $sql);
    }

    public function build(): string
    {
        if ($this->intent == 'select') {
            return $this->_build_select();
        }
        if ($this->intent == 'update') {
            return $this->_build_update();
        }
        if ($this->intent == 'delete') {
            return $this->_build_delete();
        }
        if (!$this->intent) {
            throw new \Exception('Please set intent');
        }
        return $this->_build_insert();
    }

    private function _build_insert(): string
    {
        if (empty($this->data)) {
            throw new \Exception('Insert data cannot be empty');
        }
        // 多条插入
        reset($this->data);
        $first_key = key($this->data);
        $is_batch  = is_int($first_key) && is_array(reset($this->data));
        if ($is_batch) {
            $first = reset($this->data);
            if (empty($first)) {
                throw new \Exception('Insert data row cannot be empty');
            }
            $field_keys = array_keys($first);
            $fields     = implode(',', array_map(function ($k) {
                return "`$k`";
            }, $field_keys));
            $values     = '';
            foreach ($this->data as $row) {
                $row_values = '';
                foreach ($field_keys as $key) {
                    $val = $row[$key] ?? null;
                    if (is_null($val)) {
                        $row_values .= 'NULL,';
                    } else {
                        $this->params[] = $val;
                        $row_values     .= "?,";
                    }
                }
                $row_values = substr($row_values, 0, -1);
                $values     .= "({$row_values}),";
            }
            $values = substr($values, 0, -1);
            $sql    = [$this->intent, $this->table, "({$fields})", 'VALUES', $values];
            return implode(' ', $sql);
        }

        // 单条插入
        $fields = '';
        $values = '';
        foreach ($this->data as $key => $value) {
            $fields .= "`$key`,";
            if (is_null($value)) {
                $values .= 'NULL,';
            } else {
                $this->params[] = $value;
                $values         .= "?,";
            }
        }
        $fields = substr($fields, 0, -1);
        $values = substr($values, 0, -1);

        $sql = [$this->intent, $this->table, "({$fields})", 'VALUES', "({$values})"];
        return implode(' ', $sql);
    }

    private function _build_update(): string
    {
        if (empty($this->data)) {
            throw new \Exception('Update data cannot be empty');
        }
        $set = '';
        foreach ($this->data as $key => $value) {
            if (is_null($value)) {
                $set .= "`$key`=NULL,";
            } else {
                $this->params[] = $value;
                $set            .= "`$key`=?,";
            }
        }
        $set = substr($set, 0, -1);
        if ($this->condition === null || $this->condition === '') {
            throw new \Exception('Update condition is required');
        }
        $sql = ['UPDATE', $this->table, 'SET', $set, 'WHERE', $this->condition];
        if ($this->order_by) {
            $sql[] = 'ORDER BY ' . implode(', ', $this->order_by);
        }
        if ($this->limit_offset) {
            $sql[] = "LIMIT {$this->limit_offset}";
        }
        return implode(' ', $sql);
    }

    private function _build_delete(): string
    {
        if ($this->condition === null || $this->condition === '') {
            throw new \Exception('Delete condition is required');
        }
        $sql = ['DELETE FROM', $this->table, 'WHERE', $this->condition];
        if ($this->order_by) {
            $sql[] = 'ORDER BY ' . implode(', ', $this->order_by);
        }
        if ($this->limit_offset) {
            $sql[] = "LIMIT {$this->limit_offset}";
        }
        return implode(' ', $sql);
    }

    private function _build_select(): string
    {
        //书写顺序：SELECT -> FROM -> JOIN -> ON -> WHERE -> GROUP BY -> HAVING -> UNION -> ORDER BY -> LIMIT -> FOR UPDATE
        $join_str = '';
        if ($this->joins) {
            foreach ($this->joins as $item) {
                $join_str .= "{$item['way']} JOIN {$item['table']} ON {$item['on']} ";
            }
        }

        $fields = is_array($this->fields)
            ? implode(', ', array_map(function ($f) {
                return "`$f`";
            }, $this->fields))
            : $this->fields;
        $sql    = ['SELECT', $fields, 'FROM', $this->table];
        if ($join_str !== '') {
            $sql[] = rtrim($join_str);
        }
        if ($this->condition !== null && $this->condition !== '') {
            $sql[] = 'WHERE';
            $sql[] = $this->condition;
        }
        if ($this->group_by) {
            $sql[] = "GROUP BY {$this->group_by}";
        }
        if ($this->having_condition) {
            $sql[] = "HAVING {$this->having_condition}";
        }
        if ($this->order_by) {
            $sql[] = 'ORDER BY ' . implode(', ', $this->order_by);
        }
        if ($this->limit_offset) {
            $sql[] = "LIMIT {$this->limit_offset}";
        }
        if ($this->is_for_update) {
            $sql[] = "FOR UPDATE";
        }
        return implode(' ', $sql);
    }
}