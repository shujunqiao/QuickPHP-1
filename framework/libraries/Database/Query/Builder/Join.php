<?php defined('SYSPATH') or die('No direct access allowed.');
/*
 +----------------------------------------------------------------------+
 | QuickPHP Framework Version 0.10                                      |
 +----------------------------------------------------------------------+
 | Copyright (c) 2010 QuickPHP.net All rights reserved.                 |
 +----------------------------------------------------------------------+
 | Licensed under the Apache License, Version 2.0 (the 'License');      |
 | you may not use this file except in compliance with the License.     |
 | You may obtain a copy of the License at                              |
 | http://www.apache.org/licenses/LICENSE-2.0                           |
 | Unless required by applicable law or agreed to in writing, software  |
 | distributed under the License is distributed on an 'AS IS' BASIS,    |
 | WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or      |
 | implied. See the License for the specific language governing         |
 | permissions and limitations under the License.                       |
 +----------------------------------------------------------------------+
 | Author: BoPo <ibopo@126.com>                                         |
 +----------------------------------------------------------------------+
*/
/**
 * Database query builder for JOIN statements.
 *
 * @category    QuickPHP
 * @package     Database
 * @author      BoPo <ibopo@126.com>
 * @copyright   Copyright &copy; 2010 QuickPHP
 * @license     http://www.quickphp.net/license/
 * @version     $Id: Join.php 8320 2011-10-05 14:59:55Z bopo $ 
 */
class QuickPHP_Database_Query_Builder_Join extends QuickPHP_Database_Query_Builder
{

    // JOIN查询类型: INNER, RIGHT, LEFT 等
    protected $_type;

    // JOIN ...
    protected $_table;

    // ON ...
    protected $_on = array();

    /**
     * 创建一个SQL的JOIN语句. 第二参数可选项, 可以JOIN的查询指定类型.
     *
     * @param   mixed   表名或者 array($table, $alias) 或者是对象模型
     * @param   string  JOIN查询类型: INNER, RIGHT, LEFT 等
     * @return  void
     */
    public function __construct($table, $type = null)
    {
        $this->_table = $table;

        if($type !== null)
        {
            $this->_type = (string) $type;
        }
    }

    /**
     * 增加 JOIN 条件语句(ON ...)
     *
     * @param   mixed   第一个表字段名, array($column, $alias) 数组或是对象
     * @param   string  逻辑运算
     * @param   mixed   第二个表字段名, array($column, $alias) 数组或是对象
     * @return  $this
     */
    public function on($c1, $op, $c2)
    {
        $this->_on[] = array($c1, $op, $c2);
        return $this;
    }

    /**
     * 编译 SQL JOIN 段查询语句.
     *
     * @param   object  数据库实例
     * @return  string
     */
    public function compile($db)
    {
        if($this->_type)
        {
            $sql = strtoupper($this->_type) . ' JOIN';
        }
        else
        {
            $sql = 'JOIN';
        }

        $sql .= ' ' . $db->quote_table($this->_table) . ' ON ';
        $conditions = array();

        foreach ($this->_on as $condition)
        {
            list ($c1, $op, $c2) = $condition;

            if($op)
            {
                $op = ' ' . strtoupper($op);
            }

            $conditions[] = $db->quote_identifier($c1) . $op . ' ' . $db->quote_identifier($c2);
        }

        $sql .= '(' . implode(' and ', $conditions) . ')';

        return $sql;
    }

    public function reset()
    {
        $this->_type = $this->_table = null;
        $this->_on   = array();
    }
}
