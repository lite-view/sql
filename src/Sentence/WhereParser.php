<?php

namespace LiteView\SQL\Sentence;

/**
 * WHERE 条件解析器
 *
 * 将类似 Yii2 的数组格式条件转换为 SQL 字符串片段。
 * 本类只做 SQL 字符串拼接，不处理预处理参数（? 保留原样）。
 *
 * 支持的格式示例：
 *   'id=1'                           → 字符串原样返回
 *   ['id' => 1]                      → 关联数组(哈希)，等价于 id = 1
 *   ['id' => 1, 'name' => 'xx']      → 多个键值对用 AND 连接
 *   ['id' => [1, 2, 3]]              → 值为数组自动转 IN 语句
 *   ['a=1', 'b=2']                   → 索引数组，默认用 AND 连接，等价于 ['and', 'a=1', 'b=2']
 *   ['or', 'a=1', 'b=2']             → OR 连接多个条件
 *   ['or', ['id' => 1], ['name' => 'xx']] → 嵌套子条件
 *   ['in', 'id', [1, 2, 3]]          → IN 查询
 *   ['not in', 'id', [4, 5]]         → NOT IN 查询
 *   ['between', 'age', 10, 20]       → BETWEEN 查询
 *   ['like', 'name', '%test%']       → LIKE 查询
 *   ['>', 'age', 18]                 → 比较运算符
 *   ['exists', 'SELECT ...']         → EXISTS 子查询
 */
class WhereParser
{
    /**
     * 解析条件数组/字符串，返回 SQL 条件片段（不含 WHERE 关键字）
     *
     * @param mixed $condition 条件：字符串直接返回，数组按规则解析
     * @return string SQL 条件片段
     * @throws \Exception 条件格式无效时抛出
     */
    public static function build($condition): string
    {
        return self::parse($condition);
    }

    /**
     * 递归解析条件，核心入口
     *
     * @param mixed $condition
     * @return string
     */
    private static function parse($condition): string
    {
        // 规则1: 如果是字符串，说明已经是拼接好的 SQL 片段，直接返回
        if (is_string($condition)) {
            return $condition;
        }

        // 规则2: 空数组或非数组，视为无效格式
        if (!is_array($condition) || empty($condition)) {
            throw new \Exception("Invalid condition format");
        }

        // 规则3: 关联数组（哈希格式）
        // 例如 ['id' => 1, 'name' => 'xx'] → id=1 AND name='xx'
        if (self::isAssoc($condition)) {
            $parts = [];
            foreach ($condition as $field => $value) {
                // 值如果是数组，自动按 IN 处理：['id' => [1,2,3]] → id IN (1,2,3)
                if (is_array($value)) {
                    $parts[] = self::buildIn('IN', $field, $value);
                } else {
                    $parts[] = $field . '=' . self::escapeValue($value);
                }
            }
            return implode(' AND ', $parts);
        }

        // 规则4: 索引数组，取第一个元素判断是否为操作符
        $first = strtolower((string)$condition[0]);

        // 已知的操作符列表
        if (in_array($first, ['and', 'or', 'in', 'not in', 'between', 'not between',
            'like', 'not like', 'exists', 'not exists',
            '>', '<', '>=', '<=', '<>', '!='], true)) {
            // 第一个元素是操作符：去掉头部，剩余部分作为操作数传入
            return self::buildOperator($first, array_slice($condition, 1));
        }

        // 规则5: 第一个元素不是操作符，说明是纯条件列表
        // ['a=1', 'b=2'] 等价于 ['and', 'a=1', 'b=2']，默认用 AND 连接
        return self::buildOperator('and', $condition);
    }

    /**
     * 根据操作符和操作数构建 SQL 片段
     *
     * @param string $operator 操作符: and, or, in, not in, between, not between, like, not like, exists, not exists, >, <, >=, <=, <>, !=
     * @param array  $operands 操作数列表
     * @return string
     */
    private static function buildOperator(string $operator, array $operands): string
    {
        // ----- 逻辑操作符: and / or -----
        // ['and', 'a=1', 'b=2']  →  (a=1) AND (b=2)
        // ['or', ['id'=>1], ...] →  (id=1) OR (...)
        if ($operator === 'and' || $operator === 'or') {
            $parts = [];
            foreach ($operands as $operand) {
                // 递归解析每个子条件（可能嵌套数组）
                $parsed = self::parse($operand);
                if ($parsed !== '') {
                    $parts[] = $parsed;
                }
            }
            if (empty($parts)) {
                return '';
            }
            $glue = strtoupper($operator);
            // 每个子条件用括号包裹，通过 AND/OR 连接
            return '(' . implode(") {$glue} (", $parts) . ')';
        }

        // ----- IN / NOT IN -----
        // ['in', 'id', [1,2,3]]       → id IN (1,2,3)
        // ['in', 'id', 'SELECT ...']  → id IN (SELECT ...)  子查询
        if ($operator === 'in' || $operator === 'not in') {
            if (count($operands) < 2) {
                throw new \Exception("{$operator} 条件需要指定字段和值列表");
            }
            $field  = $operands[0];
            $values = $operands[1];

            // 值是字符串 → 可能是子查询，不做转义直接使用
            if (is_string($values)) {
                $valStr = $values;
            } elseif (is_array($values)) {
                // 值列表为空，not in 永远成立，in 永远不成立
                if (empty($values)) {
                    return $operator === 'not in' ? '1=1' : '1=0';
                }
                $valStr = implode(',', array_map(['self', 'escapeValue'], $values));
            } else {
                // 单个标量值
                $valStr = self::escapeValue($values);
            }
            return $field . ' ' . strtoupper($operator) . ' (' . $valStr . ')';
        }

        // ----- BETWEEN / NOT BETWEEN -----
        // ['between', 'age', 10, 20] → age BETWEEN 10 AND 20
        if ($operator === 'between' || $operator === 'not between') {
            if (count($operands) < 3) {
                throw new \Exception("{$operator} 条件需要指定字段和两个边界值");
            }
            $field = $operands[0];
            $val1  = self::escapeValue($operands[1]);
            $val2  = self::escapeValue($operands[2]);
            return $field . ' ' . strtoupper($operator) . ' ' . $val1 . ' AND ' . $val2;
        }

        // ----- LIKE / NOT LIKE -----
        // ['like', 'name', '%test%'] → name LIKE '%test%'
        if ($operator === 'like' || $operator === 'not like') {
            if (count($operands) < 2) {
                throw new \Exception("{$operator} 条件需要指定字段和匹配值");
            }
            return $operands[0] . ' ' . strtoupper($operator) . ' ' . self::escapeValue($operands[1]);
        }

        // ----- EXISTS / NOT EXISTS -----
        // ['exists', 'SELECT 1 FROM t WHERE ...'] → EXISTS (SELECT 1 FROM t WHERE ...)
        if ($operator === 'exists' || $operator === 'not exists') {
            if (empty($operands)) {
                throw new \Exception("{$operator} 条件需要指定子查询语句");
            }
            // 支持字符串子查询或通过数组构建的嵌套条件
            $subquery = is_string($operands[0]) ? $operands[0] : self::parse($operands[0]);
            $prefix   = ($operator === 'not exists') ? 'NOT EXISTS' : 'EXISTS';
            return $prefix . ' (' . $subquery . ')';
        }

        // ----- 比较运算符: >, <, >=, <=, <>, != -----
        // ['>', 'age', 18] → age > 18
        if (count($operands) < 2) {
            throw new \Exception("{$operator} 条件需要指定字段和比较值");
        }
        return $operands[0] . ' ' . $operator . ' ' . self::escapeValue($operands[1]);
    }

    /**
     * 构建 IN 条件（供哈希格式中值为数组时调用）
     *
     * @param string $operator IN 或 NOT IN
     * @param string $field    字段名
     * @param array  $values   值列表
     * @return string
     */
    private static function buildIn(string $operator, string $field, array $values): string
    {
        if (empty($values)) {
            return $operator === 'NOT IN' ? '1=1' : '1=0';
        }
        $valStr = implode(',', array_map(['self', 'escapeValue'], $values));
        return $field . ' ' . strtoupper($operator) . ' (' . $valStr . ')';
    }

    /**
     * 对值进行 SQL 安全转义和格式化
     *
     * 规则：
     * - null     → NULL
     * - bool     → 1 / 0
     * - int/float → 直接输出数字
     * - 字符串 '?' → 保留为占位符，不加引号（预处理由外部处理）
     * - 其他字符串  → 加单引号并转义内部单引号
     *
     * @param mixed $value
     * @return string
     */
    private static function escapeValue($value): string
    {
        if ($value === null) {
            return 'NULL';
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_int($value) || is_float($value)) {
            return (string)$value;
        }
        // 字符串类型
        $value = (string)$value;
        // 预处理占位符 ? 不转义，保留原样
        if ($value === '?') {
            return '?';
        }
        return "'" . addcslashes($value, "'") . "'";
    }

    /**
     * 判断数组是否为关联数组（即是否存在非数字键）
     *
     * 关联数组(哈希): ['id' => 1, 'name' => 'xx']  → true
     * 索引数组(列表): ['a=1', 'b=2']               → false
     *
     * @param array $arr
     * @return bool
     */
    private static function isAssoc(array $arr): bool
    {
        if ([] === $arr) {
            return false;
        }
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}
