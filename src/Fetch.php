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

    public function __construct(string $sentence, array $params, Cursor $db)
    {
        $this->sentence = $sentence;
        $this->params = $params;
        $this->db = $db;
    }

    public function one()
    {
        return $this->db->prepare($this->sentence, $this->params)->fetch();
    }

    public function all($limit = null)
    {
        if (!is_null($limit)) {
            $this->sentence .= " LIMIT $limit";
        }
        return $this->db->prepare($this->sentence, $this->params)->fetchAll();
    }

    public function paginate($limit, $pageName = 'page', $page = null)
    {
        if (is_null($page)) {
            $page = 1;
            if (isset($_GET[$pageName])) {
                $page = $_GET[$pageName];
            }
        }

        $count = $this->db->prepare(
            preg_replace('/SELECT (.+?) FROM/', 'SELECT count(1) as num FROM', $this->sentence, 1),
            $this->params
        )->fetch()['num'];
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
}
