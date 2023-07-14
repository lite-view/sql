<?php


namespace LiteView\SQL;


use LiteView\SQL\Sentence\MySQL;

class GOLBuild
{
    private $cursor;
    private $table;
    private $condition;
    private $field;
    private $joins;
    private $gol;

    public function __construct($cursor, $table, $condition, $field, $joins)
    {
        $this->cursor = $cursor;
        $this->table = $table;
        $this->condition = $condition;
        $this->field = $field;
        $this->joins = $joins;
    }

    public function group($group)
    {
        $this->gol['group'] = $group;
        return $this;
    }

    public function having($having)
    {
        $this->gol['having'] = $having;
        return $this;
    }

    public function order($order)
    {
        $this->gol['order'] = $order;
        return $this;
    }

    public function limit($limit)
    {
        $this->gol['limit'] = $limit;
        return $this;
    }

    public function prep($prep = [])
    {
        if (!is_array($prep)) {
            $prep = [$prep];
        }
        $sentence = MySQL::select($this->table, $this->condition, $this->field, $this->gol, $this->joins);
        return new Fetch($sentence, $prep, $this->cursor);
    }
}