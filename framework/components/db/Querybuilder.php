<?php

/**
 * Copyright (C) 2011-2012 Shadez <https://github.com/Shadez/Framework>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 **/

namespace Db;
class QueryBuilder extends \Component
{
	protected $m_model = null;
	protected $m_fields = array();
	protected $m_childModels = array();
	protected $m_rawSql = '';
	protected $m_sql = array();
	protected $m_fieldsCount = 0;
	protected $m_functions = array();
	protected $m_changedAliases = array();
	protected $m_localeFields = array();
	protected $m_params = array();

	public function initialize()
	{
		$this->clearAliases();
		$this->m_model = null;
		$this->m_fields = array();
		$this->m_childModels = array();
		$this->m_rawSql = '';
		$this->m_sql = array();
		$this->m_fieldsCount = 0;
		$this->m_functions = array();
		$this->m_localeFields = array();

		return $this;
	}

	/**
	 * Adds MySQL function to run
	 * @param  string $function_name
	 * @param  string $field
	 * @param  string $field_alias = ''
	 * @return  QueryBuilder_Db_Component
	 **/
	public function runFunction($function_name, $field, $field_alias = '')
	{
		if (is_array($field))
		{
			$table = $field[0];
			$field = $field[1];
		}
		else
			$table = $this->getModel()->getTable();

		$this->appendSql('function', array($table, $field, $function_name, $field_alias));

		return $this;
	}

	/**
	 * Sets active model
	 * @param  string $model_name
	 * @return  QueryBuilder_Db_Component
	 * @throws \Exceptions\ModelCrash
	 **/
	public function setModel($model_name)
	{
		$this->m_model = $this->i('\Models\\' . $model_name);
		if (!$this->m_model)
			throw new \Exceptions\ModelCrash('Model ' . $model_name . ' was not found');

		$this->m_fields[$this->getModel()->getTable()] = array_keys($this->getModel()->getFields()); // Will be re-assigned, if necessary
		$this->m_fieldsCount += sizeof($this->m_fields[$this->getModel()->getTable()]);

		return $this;
	}

	/**
	 * Sets field alias
	 * @param  string $model_name
	 * @param  string $field_name
	 * @param  string $alias
	 * @return  QueryBuilder_Db_Component
	 **/
	public function setAlias($model_name, $field_name, $alias)
	{
		if (!$this->getModelByName($model_name))
			return $this;

		if (!isset($this->m_changedAliases[$model_name]))
			$this->m_changedAliases[$model_name] = array();

		$this->m_changedAliases[$model_name][$field_name] = array($field_name => $alias);

		$this->getModelByName($model_name)->m_aliases[$field_name] = $alias;

		return $this;
	}

	/**
	 * Removes dynamic aliases
	 * @return  QueryBuilder_Db_Component
	 **/
	private function clearAliases()
	{
		if (!$this->m_changedAliases)
			return $this;

		foreach ($this->m_changedAliases as $model => $aliases)
			$this->getModelByName($model)->restoreFields()->restoreAliases();

		$this->m_changedAliases = array();

		return $this;
	}

	/**
	 * Adds additional MySQL parser parameter
	 * @param  string $type
	 * @param  array $sql
	 * @return  QueryBuilder_Db_Component
	 **/
	private function appendSql($type, $sql)
	{
		if (!isset($this->m_sql[$type]))
			$this->m_sql[$type] = array();

		$this->m_sql[$type][] = $sql;

		return $this;
	}

	/**
	 * Returns active model object
	 * @return  Model_Db_Component
	 **/
	public function getModel()
	{
		return $this->m_model;
	}

	/**
	 * Finds model object by table name
	 * @param  string $t
	 * @return  Model_Db_Component
	 **/
	public function getModelByTable($t)
	{
		if ($this->m_model->m_table == $t)
			return $this->m_model;

		if (!$this->m_childModels)
			return null;

		foreach ($this->m_childModels as $class => $m)
		{
			if ($m->m_table == $t)
				return $m;
		}

		return null;
	}

	/**
	 * Finds model object by model name
	 * @param  string $n
	 * @return  Model_Db_Component
	 **/
	public function getModelByName($n)
	{
		if ($this->m_model->m_model == $n)
			return $this->m_model;

		if (!$this->m_childModels)
			return null;

		foreach ($this->m_childModels as $m)
		{
			if ($m->m_model == $n)
				return $m;
		}

		return null;
	}

	/**
	 * Sets model fields
	 * @param  string $fields
	 * @return  QueryBuilder_Db_Component
	 **/
	public function setFields($fields)
	{
		$this->m_fieldsCount = 0;
		$this->m_fields = array();
		foreach ($fields as $model => $_fields)
		{
			if (!$this->getModelByName($model))
				continue;

			$this->m_fields[$this->getModelByName($model)->m_table] = $_fields;
			
			$this->m_fieldsCount += sizeof($_fields);
		}

		return $this;
	}

	/**
	 * Returns model fields
	 * @param array &$fields
	 * @param Model_Db_Component &$model
	 * @return  void
	 * @todo Do we need this?
	 **/
	private function getFields(&$fields, &$model)
	{
		
	}

	/**
	 * Applies multi fields conditions
	 * @param  array $conditions
	 * @return  QueryBuilder_Db_Component
	 **/
	public function fieldsConditions($conditions)
	{
		if (!$conditions)
			return $this;

		foreach ($conditions as &$cond)
		{
			if (!$cond || !is_array($cond))
				continue;

			if (!isset($cond[2]))
				$cond[2] = '';

			$this->fieldCondition($cond[0], $cond[1], $cond[2]);
		}

		return $this;
	}
	
	/**
	 * Applies special MySQL condition.
	 *
	 * Since we can't apply array parameter to PDO, we're unable to use one single named parameter
	 * at some specific MySQL statements (for example, "IN" or "LIKE").
	 * This method creates named parameter for each array value and allows to create query statements like
	 * <b>"SELECT * FROM users WHERE id IN (1, 2, 3, 4, 5, 6, 7, 8, 9, 10)"</b> with simple
	 * <code>\Db\QueryResult->applyCondition('id', 'IN', array(1, ..., 10));</code>
	 *
	 * @param  string $field
	 * @param  string $condition
	 * @param  array $params = array()
	 * @param  string $next = 'AND'
	 * @return QueryBuilder_Db_Component
	 **/
	public function applyCondition($field, $condition, $params, $next = 'AND')
	{
		$sql_condition = '';

		$query_params = array();
		$param_name = '';
		foreach ($params as $p)
		{
			$param_name = ':param_' . $field . '_' . md5($p);
			$sql_condition .= ($sql_condition ? ', ' : '') . $param_name;
			$query_params[$param_name] = $p;
		}

		$field_info = explode('.', $field);

		if ($field_info && isset($field_info[1]))
		{
			$table = trim($field_info[0]);
			$field = trim($field_info[1]);
		}
		else
			$table = trim($this->getModel()->m_table);

		return $this->appendSql('where', array(
			'table' => $table,
			'field' => $field,
			'condition' => ' ' . $condition . ' ( ' . $sql_condition . ' ) ',
			'next' => $next,
			'binary' => false,
			'params' => $query_params
		));
	}

	/**
	 * Applies MySQL condition
	 * @param  string $field
	 * @param  string $condition
	 * @param  array $params = array()
	 * @param  string $next = 'AND'
	 * @param  bool $binary = false
	 * @param  string $insideCond = 'AND'
	 * @return QueryBuilder_Db_Component
	 **/
	public function fieldCondition($field, $condition, $params = array(), $next = 'AND', $binary = false, $insideCond = 'AND')
	{
		if (is_array($field))
		{
			$sqlAppend = array(
				'multi' => true,
				'conditions' => array(),
				'insideCond' => in_array(strtolower($insideCond), array('and', 'or')) ? strtoupper($insideCond) : 'AND',
				'next' => $next,
				'params' => $params
			);

			$fieldId = 0;

			foreach ($field as $model => $fields)
			{
				if (!$fields)
					continue;

				foreach ($fields as $f)
				{
					if (!isset($condition[$fieldId]))
						continue;

					$sqlAppend['conditions'][$fieldId] = array($model, $f, $condition[$fieldId]);
					++$fieldId;
				}
			}

			return $this->appendSql('where', $sqlAppend);
		}
		else
		{
			$field_info = explode('.', $field);
			if ($field_info && isset($field_info[1]))
			{
				$table = trim($field_info[0]);
				$field = trim($field_info[1]);
			}
			else
				$table = trim($this->getModel()->m_table);

			return $this->appendSql('where', array(
				'table' => $table,
				'field' => $field,
				'condition' => $condition,
				'next' => $next,
				'binary' => $binary,
				'params' => $params
			));
		}
	}

	/**
	 * Applies MySQL's LIKE operator condition
	 * @param  string $field
	 * @param  string $condition
	 * @param  string $next = 'AND'
	 * @return  QueryBuilder_Db_Component
	 **/
	public function fieldLike($field, $condition, $next = 'AND')
	{
		$field_info = explode('.', $field);
		if ($field_info && isset($field_info[1]))
		{
			$table = trim($field_info[0]);
			$field = trim($field_info[1]);
		}
		else
			$table = trim($this->getModel()->m_table);

		return $this->appendSql('where', array(
			'table' => $table,
			'field' => $field,
			'condition' => $condition,
			'next' => $next,
			'like' => true,
			'params' => array(':' . $field => $condition)
		));
	}

	/**
	 * Adds additional model to join
	 * @param  string $model_name
	 * @return  QueryBuilder_Db_Component
	 * @throws \Exceptions\ModelCrash
	 **/
	public function addModel($model_name)
	{
		if (isset($this->m_childModels[$model_name]))
			return $this;

		$this->m_childModels[$model_name] = $this->i('\Models\\' . $model_name);

		if (!$this->m_childModels[$model_name])
			throw new \Exceptions\ModelCrash('Model ' . $model_name . ' was not found!');

		$this->m_fields[$this->m_childModels[$model_name]->m_table] = array_keys($this->m_childModels[$model_name]->getFields());
		$this->m_fieldsCount += sizeof($this->m_fields[$this->m_childModels[$model_name]->m_table]);

		return $this;
	}

	/**
	 * Performs JOIN operator parsing
	 * @param  string $type
	 * @param  string $model
	 * @param  string $join
	 * @param  string $field_parent
	 * @param  string $field_child
	 * @param  array $custom_values = array()
	 * @return  QueryBuilder_Db_Component
	 **/
	public function join($type, $model, $join, $field_parent, $field_child, $custom_values = array())
	{
		if (!in_array(strtolower($type), array('left', 'right', 'inner', 'outer', '')))
			$type = 'left';
		else
			$type = strtolower($type);

		return $this->appendSql('join', array(
			'model' => $model,
			'join_model' => $join,
			'parent' => $field_parent,
			'child' => $field_child,
			'parent_value' => isset($custom_values['parent']) ? $custom_values['parent'] : null,
			'child_value' => isset($custom_values['child']) ? $custom_values['child'] : null,
			'type' => $type
		));
	}

	/**
	 * Performs ORDER BY operator parsing
	 * @param  array $fields
	 * @param  string $direct = 'asc'
	 * @return  QueryBuilder_Db_Component
	 **/
	public function order($fields, $direct = 'asc')
	{
		if (!in_array(strtolower($direct), array('asc', 'desc')))
			$direct = 'asc';
		else
			$direct = strtolower($direct);

		return $this->appendSql('order', array($fields, $direct));
	}

	/**
	 * Performs LIMIT  operator parsing
	 * @param  int $limit
	 * @param  int $offset = 0
	 * @return QueryBuilder_Db_Component
	 **/
	public function limit($limit, $offset = 0)
	{
		return $this->appendSql('limit', array($limit, $offset));
	}

	/**
	 * Sets result index field
	 * @param  string $field
	 * @return QueryBuilder_Db_Component
	 **/
	public function indexKey($field, $multy)
	{
		return $this->appendSql('key_index', array($field, $multy));
	}

	/**
	 * Returns requested items from DB
	 * @todo Cleanup requried?
	 * @return QueryBuilder_Db_Component
	 **/
	public function getItems()
	{
		$this->parseSqlData()
			->query();

		return $this;
	}

	/**
	 * Returns requested item from DB
	 * @todo Cleanup required
	 * @return QueryBuilder_Db_Component
	 **/
	public function getItem()
	{
		return $this;
	}

	private function query()
	{
		echo $this->m_rawSql;
	}

	/**
	 * Builds SQL query
	 * @return QueryBuilder_Db_Component
	 **/
	public function buildSql()
	{
		return $this->parseSqlData();
	}

	/**
	 * Returns function for a specific field
	 * @param string $table
	 * @param string $field
	 * @return string
	 **/
	public function getFunctionForField($table, $field)
	{
		if (!isset($this->m_sql['function']))
			return false;

		foreach ($this->m_sql['function'] as $func)
			if ($func[0] == $table && $func[1] == $field)
				return $func[2];	

		return '';
	}

	/**
	 * Returns alias for a specific field function
	 * @param string $table
	 * @param string $field
	 * @return string
	 **/
	public function getAliasForFieldFunction($table, $field)
	{
		if (!isset($this->m_sql['function']))
			return false;

		foreach ($this->m_sql['function'] as $func)
			if ($func[0] == $table && $func[1] == $field && isset($func[3]) && $func[3])
				return ' AS `' . $func[3] . '`';

		return '';
	}

	/**
	 * Parses SQL data into query
	 * @return QueryBuilder_Db_Component
	 **/
	private function parseSqlData()
	{
		$table_aliases = array(
			$this->getModel()->m_table => 't1'
		);

		if ($this->m_childModels)
		{
			$startIndex = 1;
			foreach ($this->m_childModels as $model)
			{
				++$startIndex;
				$table_aliases[$model->m_table] = 't' . $startIndex;
			}
		}

		// Generate SQL from internal parser
		$this->m_rawSql = $this->c('Db\Parsers\MySQL')->parseQueryBuilderSqlData(
			$table_aliases,
			$this->m_fields,
			$this->m_fieldsCount,
			$this->m_childModels,
			$this->m_sql,
			$this->m_params,
			$this,
			$this->m_localeFields
		);

		return $this;
	}

	/**
	 * Converts array type condition to string
	 * @param array $cond
	 * @param string $alias
	 * @param string $field
	 * @return string
	 **/
	public function arrayConditionToString($cond, $alias, $field)
	{
		$tmp = '';
		$size_cond = sizeof($cond);
		for ($i = 0; $i < $size_cond; ++$i)
		{
			if (!isset($cond[$i]))
				continue;

			if ($i)
				$tmp .= ',';
			if (is_integer($cond[$i]))
				$tmp .= $cond[$i];
			elseif (is_string($cond[$i]))
				$tmp .= '\'' . addslashes($cond[$i]) . '\'';
		}
		return '`' . $alias . '`.`' . $field . '` IN(' . $tmp . ')';
	}

	/**
	 * Returns Locale fields for current models
	 * @return array
	 **/
	public function getLocaleFields()
	{
		return $this->m_localeFields;
	}

	/**
	 * Returns raw SQL query
	 * @return string
	 **/
	public function getSql()
	{
		if (!$this->m_rawSql)
			$this->buildSql();

		return $this->m_rawSql;
	}

	public function getParams()
	{
		return $this->m_params;
	}

	/**
	 * Returns index key for query results
	 * @return string
	 **/
	public function getIndexKey()
	{
		return isset($this->m_sql['key_index']) ? $this->m_sql['key_index'][0] : false;
	}

	/**
	 * Performs GROUP BY operator parsing
	 * @param string $table
	 * @param string $field
	 * @return QueryBuilder_Db_Component
	 **/
	public function group($model_name, $field_name)
	{
		$this->appendSql('group', array('model' => $model_name, 'field' => $field_name));

		return $this;
	}

	public function random($val)
	{
		$this->appendSql('random', array('useRandom' => $val));

		return $this;
	}
};