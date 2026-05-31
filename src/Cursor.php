<?php


namespace LiteView\SQL;


use Exception;
use PDO;
use PDOStatement;

class Cursor
{
    private $_pdo;

    public function __construct(PDO $pdo)
    {
        $this->_pdo = $pdo;
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
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage() . "; the sql was: ====$sql====", 0, $e);
        }
    }

    // PDO::query() 执行一条SQL语句，如果通过，则返回一个PDOStatement对象。
    public function query($sql): PDOStatement
    {
        try {
            $stmt = $this->_pdo->query($sql);
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            return $stmt;
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage() . "; the sql was: ====$sql====", 0, $e);
        }
    }

    // PDOStatement::execute()函数是用于执行已经预处理过的语句，需要配合prepare()函数使用，成功时返回 TRUE， 或者在失败时返回 FALSE
    public function prepare($sql, $prep): PDOStatement
    {
        try {
            $prepare = $this->_pdo->prepare($sql);
            $prepare->setFetchMode(PDO::FETCH_ASSOC);
            foreach ($prep as $field => $value) {
                if (':' != substr($field, 0, 1)) {
                    $field = intval($field) + 1;
                }
                $type = is_null($value) ? PDO::PARAM_NULL : (is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
                $prepare->bindValue($field, $value, $type);
            }
            $prepare->execute();
            return $prepare;
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage() . "; the sql was: ====$sql====", 0, $e);
        }
    }

    public function lastInsertId(): string
    {
        return $this->_pdo->lastInsertId();
    }

    //事务
    public function transaction($func)
    {
        try {
            $this->_pdo->beginTransaction();
            $rst = $func();
            $this->_pdo->commit();
            return $rst;
        } catch (\Exception $e) {
            $this->_pdo->rollBack();
            throw new \RuntimeException($e->getMessage(), 0, $e);
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
