<?php


namespace LiteView\SQL;


use Exception;
use PDO;

class Cursor
{
    private $_pdo;

    public function __construct(PDO $pdo)
    {
        $this->_pdo = $pdo;
    }

    // PDO::query() 执行一条SQL语句，如果通过，则返回一个PDOStatement对象。
    public function query($sql)
    {
        try {
            $stmt = $this->_pdo->query($sql);
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            return $stmt;
        } catch (Exception $e) {
            trigger_error($e->getMessage() . ';this sql was : ' . $sql, E_USER_ERROR);
        }
    }

    // PDO::exec() 执行一条SQL语句，并返回受影响的行数。此函数建议用来进行新增、修改、删除
    public function exec($sql, $insert = false)
    {
        try {
            $cnt = $this->_pdo->exec($sql);
            if ($insert) {
                return $this->_pdo->lastInsertId();
            }
            return $cnt;
        } catch (Exception $e) {
            trigger_error($e->getMessage() . ';this sql was : ' . $sql, E_USER_ERROR);
        }
    }

    // PDOStatement::execute()函数是用于执行已经预处理过的语句，需要配合prepare()函数使用，成功时返回 TRUE， 或者在失败时返回 FALSE
    public function prepare($sql, $prep)
    {
        try {
            $prepare = $this->_pdo->prepare($sql);
            $prepare->setFetchMode(PDO::FETCH_ASSOC);
            foreach ($prep as $field => $value) {
                if (':' != substr($field, 0, 1)) {
                    $field = intval($field) + 1;
                }
                $prepare->bindValue($field, $value);
            }
            $prepare->execute();
            return $prepare;
        } catch (Exception $e) {
            trigger_error($e->getMessage() . ';this sql was : ' . $sql . '; prepare : ' . json_encode($prep), E_USER_ERROR);
        }
    }

    //事务
    public function transaction($func)
    {
        try {
            $this->_pdo->beginTransaction();
            $rst = $func();
            $this->_pdo->commit();
            return $rst;
        } catch (Exception $e) {
            $this->_pdo->rollBack();
            trigger_error($e->getMessage(), E_USER_ERROR);
        }
    }

    public function begin()
    {
        $this->_pdo->beginTransaction();
    }

    public function commit()
    {
        $this->_pdo->commit();
    }

    public function rollBack()
    {
        $this->_pdo->rollBack();
    }
}
