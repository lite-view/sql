<?php
/**
 * sql 查询
 */

namespace LiteView\SQL;


class Fetch
{
    private $sentence;
    private $params;
    private $db;

    private function getCountForPagination(): int
    {
        $sql = preg_replace('/SELECT (.+?) FROM/', 'SELECT count(1) as num FROM', $this->sentence, 1);
        $results = $this->db->prepare($sql, $this->params)->fetchAll();
        if (count($results) > 1) {
            return count($results);
        }
        if (isset($results[0])) {
            return (int)$results[0]['num'];
        }
        return 0;
    }

    public function __construct(string $sentence, array $params, Cursor $db)
    {
        $this->sentence = $sentence;
        $this->params = $params;
        $this->db = $db;
    }

    public function column($column = 0)
    {
        return $this->db->prepare($this->sentence, $this->params)->fetchColumn($column);
    }

    public function one()
    {
        return $this->db->prepare($this->sentence, $this->params)->fetch();
    }

    public function all($limit = null): array
    {
        if (!is_null($limit)) {
            $this->sentence .= " LIMIT $limit";
        }
        return $this->db->prepare($this->sentence, $this->params)->fetchAll();
    }

    public function paginate($limit, $pageName = 'page', $page = null): array
    {
        if (is_null($page)) {
            $page = 1;
            if (isset($_GET[$pageName])) {
                $page = $_GET[$pageName];
            }
        }

        $count = $this->getCountForPagination();
        $start = ($page - 1) * $limit;
        $this->sentence .= " LIMIT $start,$limit";
        return [
            'paging' => [
                'total' => $count,                   //数据总条数
                'pageSize' => $limit,                //每页显示条数
                'currentPage' => $page,              //当前页
                'pageCount' => ceil($count / $limit),//总页数
            ],
            'list' => $this->db->prepare($this->sentence, $this->params)->fetchAll(),
        ];
    }

    public function getRawStatement($format = false)
    {
        $stmt = "PREPARE stmt FROM '{$this->sentence}'";
        $set = null;
        $using = null;
        foreach ($this->params as $k => $v) {
            $set[] = "@param$k = '$v'";
            $using[] = "@param$k";
        }
        if ($set) {
            $set = 'SET ' . implode(',', $set);
        }
        if ($using) {
            $using = implode(',', $using);
        }
        $execute = "EXECUTE stmt USING $using";
        $deallocate = "DEALLOCATE PREPARE stmt";
        $com = compact('stmt', 'set', 'execute', 'deallocate');
        if ($format) {
            return implode(";\n", array_values($com));
        }
        $com['sentence'] = $this->sentence;
        $com['params'] = $this->params;
        return $com;
    }
}
