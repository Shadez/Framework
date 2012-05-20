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

abstract class Model_Db_Component extends Component
{
	private $m_defaultFields = array();
	private $m_defaultAliases = array();
	private $m_values = array();
	private $m_data = array();
	private $m_updatingData = false;

	protected $m_primaryFields = array();
	protected $m_primaryFieldsCount = 0;
	protected $m_rawSql = '';
	protected $m_sqlData = array();
	protected $m_validationGroups = array();

	public $m_model = 'Model';
	public $m_table = 'model';
	public $m_dbType = '';
	public $m_fields = array();
	public $m_fieldTypes = array();
	public $m_aliases = array();
	public $m_formFields = array();

	private function setProperFieldName(&$name)
	{
		$newName = '';

		if (!isset($this->m_fields[$name]))
		{
			// Check field types
			if (!isset($this->m_fieldTypes[$name]))
				return false;

			switch ($this->m_fieldTypes[$name])
			{
				case 'DbLocale':
					$newName = $name . '_' . $this->c('I18n')->getLocale(LOCALE_SINGLE);
					if (isset($this->m_fields[$newName]))
					{
						$name = $newName;
						return true;
					}
					return false;
				case 'DbLocaleId':
					$newName = $name . '_' . $this->c('I18n')->getLocaleId();
					if (isset($this->m_fields[$newName]))
					{
						$name = $newName;
						return true;
					}
					return false;
			}
		}

		return true;
	}

	public function __set($name, $value)
	{
		if (!$this->setProperFieldName($name))
			return false;

		if (!$name || !isset($this->m_fields[$name]))
			return false;

		// Validate by data type
		$value = $this->validateByType($name, $value);

		// Validate by field name
		if (method_exists($this, 'validate' . $name . 'field'))
			$value = call_user_func(array($this, 'validate' . $name . 'field'), $value);
		elseif ($this->m_validationGroups)
		{
			// Validate by validation group
			$validationGroup = '';

			foreach ($this->m_validationGroups as $group => $fields)
				if (in_array($name, $fields))
					$validationGroup = $group;

			if ($validationGroup && method_exists($this, 'validate' . $validationGroup . 'group'))
				$value = call_user_func(array($this, 'validate' . $validationGroup . 'group'), $name, $value);
		}

		$this->m_values[$name] = $value;

		return true;
	}

	public function __get($name)
	{
		if (!$this->setProperFieldName($name))
			return false;

		if (isset($this->m_values[$name]))
			return $this->m_values[$name];
		elseif (isset($this->m_data[$name]))
			return $this->m_data[$name];

		return false;
	}

	public function setData($data)
	{
		$this->m_data = $data;
		$this->m_updatingData = true;

		return $this;
	}

	public function getFieldType($name)
	{
		if (!isset($this->m_fields[$name]))
			throw new ModelCrash_Exception_Component('field "' . $name . '" was not found');

		if (is_array($this->m_fields[$name]) && isset($this->m_fields[$name]['type']))
			return $this->m_fields[$name]['type'];
		elseif ($this->m_fields[$name])
			return $this->m_fields[$name];
		else
			throw new ModelCrash_Exception_Component('unable to find "' . $name . '" field\'s type');
	}

	private function setPrimaryFields()
	{
		foreach ($this->m_fields as $field => $type)
			if (is_string($type) && $type == 'Id')
				$this->m_primaryFields[] = $field;

		$this->m_primaryFieldsCount = sizeof($this->m_primaryFields);

		return $this;
	}

	protected function validateByType($name, $value)
	{
		$type = 'string';

		if (is_array($this->m_fields[$name]) && isset($this->m_fields[$name]['type']))
			$type = $this->m_fields[$name]['type'];
		elseif ($this->m_fields[$name] == 'Id')
			$type = 'integer';

		switch ($type)
		{
			case 'integer':
				$value = (int) $value;
				break;
			case 'float':
				$value = (float) $value;
				break;
			case 'double':
				$value = (double) $value;
				break;
			case 'bool':
				$value = (bool) $value;
				break;
			case 'string':
			default:
				break;
		}

		return $value;
	}

	private function getChangedFieldsValues()
	{
		if (!$this->m_values)
			return 'NULL = NULL';

		$fields = '';

		foreach ($this->m_values as $field => $value)
		{
			$this->m_sqlData['params'][':' . $field] = $value;
			$fields .= ($this->m_updatingData ? '`' . $field . '` = ' : '') . ':' . $field . ', ';
		}

		return substr($fields, 0, (strlen($fields) - 2));
	}

	private function getChangedFields()
	{
		if (!$this->m_values)
			return '';

		$fields = '';

		foreach ($this->m_values as $field => $value)
			$fields .= '`' . $field . '`, ';

		return substr($fields, 0, (strlen($fields) - 2));
	}

	private function getFieldsForSql()
	{
		if (!$this->m_fields)
			return '';

		$fields = '';

		foreach ($this->m_fields as $field => $type)
			$fields .= '`' . $field . '`, ';

		return substr($fields, 0, (strlen($fields) - 2));
	}

	private function getPrimaryFieldsValues()
	{
		if (!$this->m_data)
			return '1';

		$sql = '';

		foreach ($this->m_primaryFields as $field)
		{
			if (!isset($this->m_data[$field]) || !$this->m_data[$field])
				return '1';

			$sql .= ($sql ? ' AND ' : '') . '`' . $field . '` = :' . $field;
			$this->m_sqlData['params'][':' . $field] = $this->m_data[$field];
		}

		return $sql;
	}

	private function generateSaveSql()
	{
		if (!$this->m_values)
			return $this;

		$this->m_rawSql = '';
		$this->m_sqlData['params'] = array();

		if ($this->m_updatingData)
			$this->m_rawSql = 'UPDATE `' . $this->getTable() . '` SET ' . $this->getChangedFieldsValues() .
			' WHERE ' . $this->getPrimaryFieldsValues() . ' LIMIT 1';
		else
			$this->m_rawSql = 'INSERT INTO `' . $this->getTable() . '` (' . $this->getChangedFields() . ') VALUES (' . $this->getChangedFieldsValues() . ')';

		return $this;
	}

	private function generateLoadSql()
	{
		$this->m_rawSql = 'SELECT ' . $this->getFieldsForSql() . ' FROM `' . $this->getTable() . '` WHERE ';
		$this->m_sqlData['params'] = array();

		if (isset($this->m_sqlData['where']))
		{
			$this->m_rawSql .= $this->m_sqlData['where']['condition'] . ' LIMIT 1';
			$this->m_sqlData['params'] = $this->m_sqlData['where']['params'];
		}
		elseif (isset($this->m_sqlData['pf']))
		{
			$this->m_data = array();

			foreach ($this->m_sqlData['pf']['values'] as $field => $value)
				$this->m_data[str_replace(':', '', $field)] = $value;

			$this->m_rawSql .= $this->getPrimaryFieldsValues() . ' LIMIT 1';
		}
		elseif (isset($this->m_sqlData['random']) && $this->m_sqlData['random'])
		{
			$this->m_rawSql .= '1 ORDER BY RAND() LIMIT 1';
		}

		return $this;
	}

	private function generateDeleteSql()
	{
		if (!$this->m_data && !$this->m_values)
			return $this;

		$this->m_sqlData['params'] = array();
		$this->m_rawSql = 'DELETE FROM `' . $this->getTable() . '` WHERE ' . $this->getPrimaryFieldsValues() . ' LIMIT 1';

		return $this;
	}

	private function performSql($load = false)
	{
		if (!$this->m_rawSql)
			return false;

		$result = true;
		$params = isset($this->m_sqlData['params']) ? $this->m_sqlData['params'] : array();

		if ($load)
		{
			$result = $this->c('Db')->getDb($this->getType())->selectWithParams($this->m_rawSql, $params)->getData();

			if (isset($result[0]) && $result[0])
				$this->setData($result[0]);
		}
		else
			$this->c('Db')->getDb($this->getType())->executeWithParams($this->m_rawSql, $params);

		return $this;
	}

	public function initialize()
	{
		$this->m_defaultFields = $this->m_fields;
		$this->m_defaultAliases = $this->m_aliases;

		return $this->setPrimaryFields();
	}

	public function getName()
	{
		return $this->m_model;
	}

	public function getTable()
	{
		return $this->m_table;
	}

	public function getType()
	{
		return $this->m_dbType;
	}

	public function getFields()
	{
		return $this->m_fields;
	}

	public function getAliases()
	{
		return $this->m_aliases;
	}

	public function getFieldTypes()
	{
		return $this->m_fieldTypes;
	}

	public function restoreFields()
	{
		$this->m_fields = $this->m_defaultFields;

		return $this;
	}

	public function restoreAliases()
	{
		$this->m_aliases = $this->m_defaultAliases;

		return $this;
	}

	protected function updateFields()
	{
		if (!$this->m_values)
			return $this;

		foreach ($this->m_values as $f => $v)
			$this->m_data[$f] = $v;

		return $this;
	}

	/**
	 * load(array('id' => 5));
	 **/
	public function load($primaryfieldsValues)
	{
		$this->m_sqlData['pf'] = array(
			'values' => $primaryfieldsValues
		);

		return $this->generateLoadSql()
			->performSql(true);
	}

	public function loadRandom()
	{
		$this->m_sqlData['random'] = true;

		return $this->generateLoadSql()
			->performSql(true);
	}

	/**
	 * find('`id` = :id AND (`name` LIKE \'%:name%\' OR `login` LIKE \'%:name%\')', array('id' => 5, 'name' => 'Shadez'));
	 **/
	public function find($condition, $values)
	{
		$this->m_sqlData['where'] = array(
			'condition' => $condition,
			'params' => $values
		);

		return $this->generateLoadSql()
			->performSql(true);
	}

	public function save()
	{
		if (!$this->m_values)
			return true; // No changes were made

		return $this->generateSaveSql()
			->updateFields()
			->performSql();
	}

	public function delete()
	{
		if (!$this->m_data)
			return true; // No data

		return $this->generateDeleteSql()
			->performSql();
	}
};