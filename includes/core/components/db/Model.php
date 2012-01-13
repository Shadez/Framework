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

class Model_Db_Component extends Component
{
	public $m_model  = '';
	public $m_table  = '';
	public $m_dbType = '';
	public $m_fields = array();
	public $m_callbackComponent = null;
	public $m_types = array();
	public $m_aliases = array();

	private $m_itemID = 0;
	private $m_conditions = array();
	private $m_data = array();
	private $m_insert_id = 0;
	private $m_sql = '';
	private $m_params = array();
	private $m_queryFields = array();
	private $m_sqlData = array();
	private $m_isRandom = false;
	private $m_isJoining = false;
	private $m_joinCondition = array();
	private $m_joinModels = array();
	private $m_defaultFields = array();
	private $m_defaultAliases = array();

	public function initialize()
	{
		$this->m_types['default'] = array_values($this->m_fields);

		$this->m_defaultFields = $this->m_fields;
		$this->m_defaultAliases = $this->m_aliases;

		return $this;
	}

	protected function prepareFields()
	{
		$data = array();
		$locale = $this->c('Locale')->GetLocale();

		foreach ($this->m_fields as $field => $value)
		{
			if (is_array($value))
				continue;

			switch($value)
			{
				case 'Locale':
					if (preg_match('/_' . $locale . '/', $field))
						continue;
					
					$data[] = array('alias' => $field, 'name' => $this->c($value, 'Field')->prepareField($field), 'value' => $value);
					break;
			}
		}

		if ($data)
		{
			foreach ($data as $field)
			{
				$this->m_aliases[$field['name']] = $field['alias'];
				unset($this->m_fields[$field['alias']]);
				$this->m_fields[$field['name']] = $field['value'];
			}
		}
		
		if (!$this->m_queryFields)
			$this->m_queryFields = array_keys($this->m_fields);
	}

	public function setConditions($conditions)
	{
		$this->m_conditions = $conditions;

		return $this;
	}

	public function findParams($params)
	{
		if (isset($params['order']))
			$this->m_params['order'] = $params['order'];

		if (isset($params['order_type']))
			$this->m_params['order_type'] = in_array(strtolower($params['order_type']), array('asc', 'desc')) ? strtoupper($params['order_type']) : 'ASC';

		if (isset($params['group']))
			$this->m_params['group'] = $params['group'];

		if (isset($params['limit']))
			$this->m_params['limit'] = $params['limit'];

		if (isset($params['fields']))
			$this->m_queryFields = $params['fields'];

		if (isset($params['join_models']))
			$this->setJoinModels($params['join_models']);

		return $this;
	}

	private function setJoinModels($models)
	{
		if (is_array($models))
		{
			foreach($models as $model)
			{
				$name = $model . '_Model_Component';
				$this->m_joinModels[$name] = new $name($name . '_Model', $this->core);
				$this->addFields($this->m_joinModels[$name]);
			}
		}

		return $this;
	}

	private function addFields($model)
	{
		if (!$model)
			return $this;

		foreach($model->m_fields as $name => $value)
			$this->m_fields[$name] =  $value;

		return $this;
	}
	
	public function setFields($fields)
	{
		if ($this->m_queryFields)
			return $this;

		$this->m_queryFields = $fields;

		return $this;
	}

	public function setValues($values)
	{
		$this->m_conditions = /*$this->m_conditions +*/ $values;

		return $this;
	}

	public function setItemRandom()
	{
		$this->m_isRandom = true;
	}

	public function setJoin($conditions)
	{
		if ($conditions)
		{
			$this->m_isJoining = true;
			$this->m_joinCondition = $conditions;
		}

		return $this;
	}

	public function getData()
	{
		return $this->m_data;
	}

	protected function requestData($type = 'select')
	{
		if ($this->m_callbackComponent)
			$this->c($this->m_callbackComponent['name'], $this->m_callbackComponent['type'])->onDataRequest($this->m_sqlData);

		$this->m_data = call_user_func_array(array($this->c('Db')->{$this->m_dbType}(), $type), $this->m_sqlData);

		if ($this->m_callbackComponent)
			$this->c($this->m_callbackComponent['name'], $this->m_callbackComponent['type'])->onDataReceive($this->m_data);

		return $this;
	}

	public function loadItem($sql)
	{
		$this->m_sqlData = array($sql);
		return $this->requestData('selectRow');
	}
	
	public function loadItems($sql)
	{
		$this->m_sqlData = array($sql);
		return $this->requestData()
			->parseItemsData();
	}

	public function setItem($id, $core_var = null)
	{
		if ($core_var != null && $this->core->getVar($core_var) != null)
			$this->m_itemID = $this->core->getVar($core_var);
		else
			$this->m_itemID = $id;

		return $this;
	}

	public function query($type = 'default')
	{
		return $this;
	}

	private function parseItemsData()
	{
		if (!$this->m_data)
			return $this;

		foreach ($this->m_data as &$data)
			foreach ($data as $key => &$value)
				if (isset($this->m_fields[$key]) && is_string($this->m_fields[$key]))
					$this->c($this->m_fields[$key], 'Field')->convertToField($data, $key);

		return $this;
	}

	private function parseItemData()
	{
		return $this;
	}

	private function parseSql()
	{
		$this->prepareFields();

		$this->m_sql = '';
		$this->m_sqlData = array();

		$this->m_sql .= 'SELECT ';
		$this->m_sqlData[] = 'NULL QUERY';

		if ($this->m_queryFields)
		{
			$size = sizeof($this->m_queryFields);
			$table_id = 1;
			$tables = array($this->m_table => 't' . $table_id);
			++$table_id;
			for ($i = 0; $i < $size; ++$i)
			{
				$table_select_as = $tables[$this->m_table];
				$table_alias = isset($this->m_aliases[$this->m_queryFields[$i]]) ? $this->m_aliases[$this->m_queryFields[$i]] : $this->m_queryFields[$i];
				if (!isset($this->m_fields[$this->m_queryFields[$i]]))
				{
					// Check other models
					if (strpos($this->m_queryFields[$i], '.') !== false)
					{
						$field = explode('.', $this->m_queryFields[$i]);
						if ($field && sizeof($field) == 2)
						{
							// table.field
							$table = $field[0];
							$field = $field[1];
							if (!isset($tables[$table]))
							{
								$tables[$table] = 't' . $table_id;
								++$table_id;
								$table_select_as = $tables[$table];
								$table_alias = $field;
								$field_name = $field;
							}
						}
					}
				}
				else
					$field_name = $this->m_queryFields[$i];

				$this->m_sql .= '`' . $table_select_as . '`.`' . $field_name . '` ' . ($table_alias != null ? ' AS `' . $table_alias . '` ' : '');
				
				if ($i < $size-1)
					$this->m_sql .= ', ';
			}
			$this->m_sql .= ' FROM `' . $this->m_table . '`' . (isset($tables[$this->m_table]) ? ' AS `' . $tables[$this->m_table] . '` ' : '');
			if (sizeof($tables) > 1 && $this->m_isJoining && $this->m_joinCondition)
			{
				// Join
				foreach ($this->m_joinCondition as &$cond)
				{
					if (!isset($cond['type']) || !isset($cond['table']) || !isset($cond['field']) || !isset($cond['parent_table']) || !isset($cond['parent_field']) || !isset($cond['value']) || !isset($cond['join_type']))
						continue; // Not enough actual data

					if (!isset($tables[$cond['table']]))
						continue; // Wrong table

					$this->m_sql .= ' ' . strtoupper($cond['join_type']) . ' JOIN `' . $cond['table'] . '` AS `' . $tables[$cond['table']] . '` ON ';
					if ((is_array($cond['field']) && is_array($cond['table']) && is_array($cond['parent_field']) && is_array($cond['parent_table'])) 
						&& (sizeof($cond['field']) == sizeof($cond['table']) && sizeof($cond['parent_field']) == sizeof($cond['field']) && sizeof($cond['parent_table']) == sizeof($cond['field'])))
					{
						// Multijoin
						$cond_size = sizeof($cond['value']);
						for ($i = 0; $i < $cond_size; ++$i)
						{
							if (!isset($tables[$cond['parent_table'][$i]]))
								continue; // Wrong table name

							$this->m_sql .= '`' . $tables[$tables[$cond['parent_table'][$i]]] . '`.`' . $cond['parent_field'][$i] . '` ' . $cond['type'][$i] . ' ';
							$this->m_sql .= (in_array($cond['field'], $this->m_queryFields) ? '`' . $tables[$cond['table'][$i]] . '`.`' . $cond['field'][$i] . '`' : in_array($cond['table'][$i] . '.' . $cond['field'][$i], $this->m_queryFields) ? '`' . $tables[$cond['table'][$i]] . '`.`' . $cond['field'][$i] . '`' : "'" . $cond['value'] . "'");
							$this->m_sql .= ' ';
						}
					}
					else
					{
						$this->m_sql .= '`' . $tables[$cond['parent_table']] . '`.`' . $cond['parent_field'] . '` ' . $cond['type'] . ' `' . $tables[$cond['table']] . '`.`' . $cond['field'] . '` ';
					}
				}
			}
		}

		if ($this->m_conditions)
		{
			$size = sizeof($this->m_conditions);

			if ($size > 0)
				$this->m_sql .= ' WHERE ';

			for ($i = 0; $i < $size; ++$i)
			{
				if (!isset($this->m_conditions[$i]['field']) || !isset($this->m_conditions[$i]['type']) || !isset($this->m_conditions[$i]['value']))
					continue;

				if (isset($this->m_conditions[$i]['table']))
				{
					if (isset($tables[$this->m_conditions[$i]['table']]))
						$this->m_sql .= ' `' . $tables[$this->m_conditions[$i]['table']] . '`.';
				}
				elseif (isset($tables[$this->m_table]))
					$this->m_sql .= ' `' . $tables[$this->m_table] . '`.';

				$this->m_sql .= '`' . $this->m_conditions[$i]['field'] . '` ' . $this->m_conditions[$i]['type'] . ' \'' . $this->m_conditions[$i]['value'] . '\'';

				if ($i < $size-1)
					$this->m_sql .= isset($this->m_conditions[$i+1]['typeAndOr']) ? $this->m_conditions[$i + 1]['typeAndOr'] . ' ' : '';
			}
		}

		if (isset($this->m_params['order']) && !$this->m_isRandom)
		{
			$this->m_sql .= ' ORDER BY ';
			$size = sizeof($this->m_params['order']);
			for($i = 0; $i < $size; ++$i)
			{
				$this->m_sql .= ' `' . $this->m_params['order'][$i] . '`';
				if ($i < $size-1)
					$this->m_sql .= ', ';
			}
			if (isset($this->m_params['order_type']))
				$this->m_sql .= ' ' . $this->m_params['order_type'];
		}
		elseif ($this->m_isRandom)
			$this->m_sql .= ' ORDER BY RAND() ';

		if (isset($this->m_params['group']))
			$this->m_sql .= ' GROUP BY ' . $this->m_params['group'];

		if (isset($this->m_params['limit']))
		{
			$this->m_sql .= ' LIMIT ' . $this->m_params['limit'];
		}

		$this->m_sqlData[0] = $this->m_sql;

		if (!$this->c('Config')->getValue('database.' . $this->m_dbType . '.host'))
		{
			// Multiply
			$this->c('Db')->switchTo($this->m_dbType, $this->c('Config')->getValue('tmp.db_' . $this->m_dbType . '.active'));
		}

		return $this;
	}

	protected function getModelIdField()
	{
		foreach ($this->m_fields as $field => $type)
			if (is_string($type) && $type == 'Id')
				return $field;

		return 'id';
	}

	public function getItem($id, $fields = array())
	{
		if ($fields)
			$this->m_queryFields = $fields;

		$this->setConditions(array(
			array(
				'field' => $this->getModelIdField(),
				'type' => '=',
				'value' => $id,
				'typeAndOr' => 'AND'
			)
		));

		return $this->getLoadItem()->getData();
	}

	protected function getLoadItem()
	{
		return $this->parseSql()
			->requestData('selectRow');
	}

	protected function getLoadItems()
	{
		return $this->parseSql()->requestData()
			->parseItemsData();
	}

	public function getItems($fields = array(), $conditions = array())
	{
		$this->setConditions($conditions);

		if ($fields)
			$this->m_queryFields = $fields;

		return $this->getLoadItems()->getData();
	}

	//!
	public function getFields()
	{
		$data = array();
		$localePattern = '/_' . $this->c('Locale')->GetLocale() . '/';
		$dbLocalePattern = '/_loc' . $this->c('Locale')->GetLocaleID() . '/';

		foreach($this->m_fields as $field => $type)
		{
			if (!is_string($type))
				continue;

			switch($type)
			{
				case 'Locale':
					if (preg_match($localePattern, $field))
						continue;

					$new = $field . '_' . $this->c('Locale')->getLocale();
					$this->m_aliases[$new] = $field;
					$data[] = array($field, $new);
					break;
				case 'DbLocale':
					if (preg_match($dbLocalePattern, $field))
						continue;

					$new = $field . '_loc' . $this->c('Locale')->getLocaleId();
					$this->m_aliases[$new] = $field;
					$data[] = array($field, $new);
					break;
			}
		}

		if ($data)
		{
			foreach ($data as $info)
			{
				$tmp = $this->m_fields[$info[0]];
				$this->m_fields[$info[1]] = $tmp;
				unset($this->m_fields[$info[0]]);
			}
		}

		return $this->m_fields;
	}

	public function removeAliasByFieldName($fieldName)
	{
		if (isset($this->m_aliases[$fieldName]))
			unset($this->m_aliases[$fieldName]);

		return $this;
	}

	public function removeAliasByAliasName($aliasName)
	{
		if (!$this->m_aliases)
			return $this;

		$fieldToRemove = false;

		foreach ($this->m_aliases as $field => $alias)
		{
			if ($aliasName == $alias)
			{
				$fieldToRemove = $field;
				break;
			}
		}

		if ($fieldToRemove && isset($this->m_aliases[$fieldToRemove]))
			unset($this->m_aliases[$fieldToRemove]);

		return $this;
	}

	//!
	public function restoreFields()
	{
		$this->m_fields = $this->m_defaultFields;

		return $this;
	}

	//!
	public function restoreAliases()
	{
		$this->m_aliases = $this->m_defaultAliases;

		return $this;
	}
}