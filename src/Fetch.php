<?php
/**
 * sql 查询
 */

namespace LiteView\SQL;


class Fetch
{
    private $builder;
    private $params;
    private $db;

    public function __construct($builder, array $params, Cursor $db)
    {
        $this->builder = $builder;
        $this->params  = $params;
        $this->db      = $db;
    }

    public function column($column = 0)
    {
        $sentence = $this->builder->build();
        return $this->db->prepare($sentence, $this->params)->fetchColumn($column);
    }

    public function one()
    {
        $sentence = $this->builder->build();
        return $this->db->prepare($sentence, $this->params)->fetch();
    }

    public function all($limit = null): array
    {
        $sentence = $this->builder->limit($limit)->build();
        return $this->db->prepare($sentence, $this->params)->fetchAll();
    }

    public function paginate($limit, $pageName = 'page', $page = null): array
    {
        if (is_null($page)) {
            $page = 1;
            if (isset($_GET[$pageName])) {
                $page = $_GET[$pageName];
            }
        }

        $count    = $this->getCountForPagination();
        $offset   = ($page - 1) * $limit;
        $sentence = $this->builder->limit($limit, $offset)->build();
        return [
            'paging' => [
                'total'       => $count,                   //数据总条数
                'pageSize'    => $limit,                //每页显示条数
                'currentPage' => $page,              //当前页
                'pageCount'   => ceil($count / $limit),//总页数
            ],
            'list'   => $this->db->prepare($sentence, $this->params)->fetchAll(),
        ];
    }

    private function getCountForPagination(): int
    {
        $builder  = clone $this->builder;
        $sentence = $builder->count();
        return $this->db->prepare($sentence, $this->params)->fetchColumn();
    }

    public function getRawStatement($format = false)
    {
        $sentence = $this->builder->build();
        $stmt     = "PREPARE stmt FROM '{$sentence}'";
        $set      = null;
        $using    = null;
        foreach ($this->params as $k => $v) {
            $set[]   = "@param$k = '$v'";
            $using[] = "@param$k";
        }
        if ($set) {
            $set = 'SET ' . implode(',', $set);
        }
        if ($using) {
            $using = implode(',', $using);
        }
        $execute    = "EXECUTE stmt USING $using";
        $deallocate = "DEALLOCATE PREPARE stmt";
        $com        = compact('stmt', 'set', 'execute', 'deallocate');
        if ($format) {
            return implode(";\n", array_values($com));
        }
        $com['sentence'] = $sentence;
        $com['params']   = $this->params;
        return $com;
    }
}
