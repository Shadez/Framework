<?php

/**
 * Copyright (C) 2009-2012 Shadez <https://github.com/Shadez>
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

class Query_Component extends Component
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

	public function clear()
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

		return 0;
	}

	public function runFunction($function_name, $field, $field_alias = '')
	{
		if (is_array($field))
		{
			$table = $field[0];
			$field = $field[1];
		}
		else
			$table = $this->getModel()->m_table;

		$this->appendSql('function', array($table, $field, $function_name, $field_alias));

		return $this;
	}

	public function setModel($model_name)
	{
		$this->m_model = $this->i($model_name, 'Model');
		if (!$this->m_model)
			throw new ModelCrash_Exception_Component('Model ' . $model_name . ' was not found!');

		$this->m_fields[$this->getModel()->m_table] = array_keys($this->getModel()->getFields()); // Will be re-assigned, if necessary
		$this->m_fieldsCount += sizeof($this->m_fields[$this->getModel()->m_table]);

		return $this;
	}

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

	private function clearAliases()
	{
		if (!$this->m_changedAliases)
			return $this;

		foreach ($this->m_changedAliases as $model => $aliases)
			$this->getModelByName($model)->restoreFields()->restoreAliases();

		$this->m_changedAliases = array();

		return $this;
	}

	private function appendSql($type, $sql)
	{
		if (!isset($this->m_sql[$type]))
			$this->m_sql[$type] = array();

		$this->m_sql[$type][] = $sql;

		return $this;
	}

	public function getModel()
	{
		return $this->m_model;
	}

	protected function getModelByTable($t)
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

	protected function getModelByName($n)
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

	private function getFields(&$fields, &$model)
	{
		
	}

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
	 * @param string $field
	 * @param mixed  $condition
	 * @param string $next = 'AND'
	 * @param bool   $binary = false
	 **/
	public function fieldCondition($field, $condition, $next = 'AND', $binary = false)
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
			'binary' => $binary
		));
	}

	public function addModel($model_name)
	{
		if (isset($this->m_childModels[$model_name]))
			return $this;

		$this->m_childModels[$model_name] = $this->i($model_name, 'Model');

		if (!$this->m_childModels[$model_name])
			throw new ModelCrash_Exception_Component('Model ' . $model_name . ' was not found!');

		$this->m_fields[$this->m_childModels[$model_name]->m_table] = array_keys($this->m_childModels[$model_name]->getFields());
		$this->m_fieldsCount += sizeof($this->m_fields[$this->m_childModels[$model_name]->m_table]);

		return $this;
	}

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

	public function order($fields, $direct = 'asc')
	{
		if (!in_array(strtolower($direct), array('asc', 'desc')))
			$direct = 'asc';
		else
			$direct = strtolower($direct);

		return $this->appendSql('order', array($fields, $direct));
	}

	public function limit($limit, $offset = 0)
	{
		return $this->appendSql('limit', array($limit, $offset));
	}

	public function keyIndex($field)
	{
		return $this->appendSql('key_index', $field);
	}

	public function getItems()
	{
		$this->parseSqlData()
			->query();

		return $this;
	}

	public function getItem()
	{
		return $this;
	}

	private function query()
	{
		echo $this->m_rawSql;
	}

	public function buildSql()
	{
		return $this->parseSqlData();
	}

	private function getFunctionForField($table, $field)
	{
		if (!isset($this->m_sql['function']))
			return false;

		foreach ($this->m_sql['function'] as $func)
			if ($func[0] == $table && $func[1] == $field)
				return $func[2];	

		return '';
	}

	private function getAliasForFieldFunction($table, $field)
	{
		if (!isset($this->m_sql['function']))
			return false;

		foreach ($this->m_sql['function'] as $func)
			if ($func[0] == $table && $func[1] == $field && isset($func[3]) && $func[3])
				return ' AS `' . $func[3] . '`';

		return '';
	}

	private function parseSqlData()
	{
		$time_start = microtime(true);

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

		$this->m_rawSql = 'SELECT' . NL;
		$field_num = 0;

		$this->m_localeFields = array();

		foreach ($this->m_fields as $tName => $table)
		{
			if (!$tName || !$table)
				continue;

			$model = $this->getModelByTable($tName);
			if (!$model)
				continue;

			$alias = $table_aliases[$tName];
			if (!$alias)
				$alias = 't' . rand(50, 100);

			$size_fields = sizeof($table);
			for ($i = 0; $i < $size_fields; ++$i)
			{
				if (!isset($table[$i]) || !$table[$i])
					continue;

				$skipAs = false;

				if (isset($this->m_sql['function']))
				{
					$function = $this->getFunctionForField($this->getModel()->m_table, $table[$i]);
					if ($function)
					{
						$alias_f_func = $this->getAliasForFieldFunction($this->getModel()->m_table, $table[$i]);
						if ($alias_f_func)
							$skipAs = true;

						$this->m_rawSql .= strtoupper($function) . '(' . '`' . $alias . '`.`' . $table[$i] . '`' . ')' . $alias_f_func;
					}
					else
						$this->m_rawSql .= '`' . $alias . '`.`' . $table[$i] . '`';
				}
				else
					$this->m_rawSql .= '`' . $alias . '`.`' . $table[$i] . '`';

				if (!$skipAs)
				{
					$tempAlias = null;
					// Check if this field is DbLocale field
					// If it is, set it to temporary name (for cases when localization is missing in DB0)
					if (isset($model->m_fields[$table[$i]]) && $model->m_fields[$table[$i]] == 'DbLocale')
					{
						if (!isset($this->m_localeFields[$model->m_table]))
							$this->m_localeFields[$model->m_table] = array();

						$this->m_localeFields[$model->m_table][$table[$i] . '_temporary'] = array(
							'field' => $table[$i],
							'temp'  => $table[$i] . '_temporary',
							'alias' => (isset($model->m_aliases[$table[$i]]) ? $model->m_aliases[$table[$i]] : $table[$i])
						);
						$tempAlias = $table[$i] . '_temporary';
					}

					$this->m_rawSql .= ' AS `';

					if ($tempAlias != null)
						$this->m_rawSql .= $tempAlias;
					elseif (isset($model->m_aliases[$table[$i]]))
						$this->m_rawSql .= $model->m_aliases[$table[$i]];
					else
						$this->m_rawSql .= $table[$i];

					$this->m_rawSql .= '`';
				}

				$this->m_rawSql .= NL;

				$field_num++;

				if ($field_num < $this->m_fieldsCount)
					$this->m_rawSql .= ',';
			}
		}
		$this->m_rawSql .= 'FROM `' . $this->getModel()->m_table . '` AS `' . $table_aliases[$this->getModel()->m_table] . '`' . NL;

		if (isset($this->m_sql['join']))
		{
			$join_sql = array();

			$join_size = sizeof($this->m_sql['join']);

			for ($i = 0; $i < $join_size; ++$i)
			{
				$j = &$this->m_sql['join'][$i];

				if (!isset($j['model']) || !isset($this->m_childModels[$j['model']]))
					continue;

				if (!isset($join_sql[$j['model']]))
					$join_sql[$j['model']] = '';

				$alias = $table_aliases[$this->m_childModels[$j['model']]->m_table];

				$mJoin_table = $this->getModel()->m_table;
				$mJoin_alias = $table_aliases[$this->getModel()->m_table];

				if (isset($j['join_model']))
				{
					$model = $this->getModelByName($j['join_model']);
					if ($model)
					{
						$mJoin_table = $model->m_table;
						$mJoin_alias = $table_aliases[$model->m_table];
					}
				}

				if ($join_sql[$j['model']] == '')
				{
					$join_sql[$j['model']] .= strtoupper($j['type']) . ' JOIN `' . $this->m_childModels[$j['model']]->m_table . '`';
					$join_sql[$j['model']] .= ' AS `' . $alias . '` ON `' . $alias . '`.`' . $j['child'] . '` = ';

					if (!$j['child_value'])
						$join_sql[$j['model']] .= '`' . $mJoin_alias . '`.`' . $j['parent'] . '`';
					else
						$join_sql[$j['model']] .= '\'' . $j['child_value'] . '\'';
				}
				else
				{
					$join_sql[$j['model']] .= ' AND `' . $alias . '`.`' . $j['child'] . '` = ';

					if (!$j['child_value'])
						$join_sql[$j['model']] .= '`' . $mJoin_alias . '`.`' . $j['parent'] . '`';
					else
						$join_sql[$j['model']] .= '\'' . $j['child_value'] . '\'';
				}

				$join_sql[$j['model']] .= NL;
			}

			if ($join_sql)
				foreach ($join_sql as $join_rawSql)
					$this->m_rawSql .= $join_rawSql;
		}

		if (isset($this->m_sql['where']))
		{
			$this->m_rawSql .= 'WHERE' . NL;

			$changed = false;

			$count = sizeof($this->m_sql['where']);
			$current = 0;

			foreach ($this->m_sql['where'] as $cond)
			{
				if (!isset($cond['table']) || !isset($cond['field']) || !isset($cond['condition']))
					continue;

				$alias = $table_aliases[$cond['table']];
				if (is_array($cond['condition']))
				{
					$tmp = '';
					$size_cond = sizeof($cond['condition']);
					for ($i = 0; $i < $size_cond; ++$i)
					{
						if (!isset($cond['condition'][$i]))
							continue;

						if ($i)
							$tmp .= ',';
						if (is_integer($cond['condition'][$i]))
							$tmp .= $cond['condition'][$i];
						elseif (is_string($cond['condition'][$i]))
							$tmp .= '\'' . addslashes($cond['condition'][$i]) . '\'';
					}
					$this->m_rawSql .= '`' . $alias . '`.`' . $cond['field'] . '` IN(' . $tmp . ')';
				}
				else
					$this->m_rawSql .= ($cond['binary'] ? ' BINARY ' : '') . '`' . $alias . '`.`' . $cond['field'] . '`' . $cond['condition'];

				++$current;
				if ($current < $count)
				{
					if (!isset($cond['next']))
						$this->m_rawSql .= ' AND';
					else
						$this->m_rawSql .= ' ' . $cond['next'];
				}

				$changed = true;

				$this->m_rawSql .= NL;
			}
			if (!$changed)
				$this->m_rawSql .= ' 1' . NL;
		}

		if (isset($this->m_sql['group']))
		{
			$g = $this->m_sql['group'][0];
			if ($g)
				$this->m_rawSql .= 'GROUP BY `' . $table_aliases[$this->getModelByName($g['model'])->m_table] . '`.`' . $g['field'] . '`' . NL;
		}

		if (isset($this->m_sql['order']))
		{
			$this->m_rawSql .= 'ORDER BY ' . NL;
			$multiorder = false;
			foreach ($this->m_sql['order'] as &$entry)
			{
				if (!isset($entry[0]) || !is_array($entry[0]))
					continue;

				$fields_info = $entry[0];
				$type = $entry[1];
				foreach ($fields_info as $model => &$fields)
				{
					$m = $this->getModelByName($model);
					if (!$m)
						continue;

					$current = 0;
					$size = sizeof($fields);

					foreach ($fields as $probKey => &$field)
					{
						if (is_array($field) && (!is_numeric($probKey) && is_string($probKey)))
						{
							// Using multi order
							$multiorder = true;
							$this->m_rawSql .= '`' . $table_aliases[$m->m_table] . '`.`' . $probKey . '` ' . strtoupper($field[0]);
						}
						else
							$this->m_rawSql .= '`' . $table_aliases[$m->m_table] . '`.`' . $field . '` ';

						if ($current < $size-1)
							$this->m_rawSql .= ', ';
		
						++$current;
					}
				}
			}
			if (!$multiorder)
				$this->m_rawSql .= ' ' . strtoupper($type);
		}

		if (isset($this->m_sql['limit']))
		{
			if (isset($this->m_sql['limit'][0][1]))
			{
				$this->m_rawSql .= ' LIMIT ' . $this->m_sql['limit'][0][1];
				if (isset($this->m_sql['limit'][0][0]))
					$this->m_rawSql .= ', ' . $this->m_sql['limit'][0][0];

				$this->m_rawSql .= NL;
			}
		}

		return $this;
	}

	public function getLocaleFields()
	{
		return $this->m_localeFields;
	}

	public function getSql()
	{
		if (!$this->m_rawSql)
			$this->buildSql();

		return $this->m_rawSql;
	}

	public function getIndexKey()
	{
		return isset($this->m_sql['key_index']) ? $this->m_sql['key_index'][0] : false;
	}

	public function group($model_name, $field_name)
	{
		$this->appendSql('group', array('model' => $model_name, 'field' => $field_name));

		return $this;
	}
}