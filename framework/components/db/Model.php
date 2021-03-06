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
abstract class Model extends \Component
{
	private $m_defaultFields = array();
	private $m_defaultAliases = array();

	protected $m_values = array();
	protected $m_data = array();
	protected $m_updatingData = false;
	protected $m_dataLoaded = false;
	protected $m_returnInsertId = false;
	protected $m_lastInsertId = 0;
	protected $m_primaryFields = array();
	protected $m_primaryFieldsCount = 0;
	protected $m_rawSql = '';
	protected $m_sqlData = array();
	protected $m_validationGroups = array();
	protected $m_customData = array();

	public $m_model = 'Model';
	public $m_table = 'model';
	public $m_dbType = '';
	public $m_fields = array();
	public $m_fieldTypes = array();
	public $m_aliases = array();
	public $m_formFields = array();

	public function onModelDataSelection($event) {}
	public function onModelDataUpdate($event) {}
	public function onBeforeModelDataSave($event) {}
	public function onAfterModelDataSave($event) {}
	public function onModelFind($event) {}
	public function onModelLoad($event) {}
	public function onModelLoadRandom($event) {}
	public function onModelSave($event) {}
	public function onModelDelete($event) {}

	public function initialize()
	{
		$this->c('Events')
			->createEvent('onModelDataSelection', array($this, 'onModelDataSelection'))
			->createEvent('onModelDataUpdate', array($this, 'onModelDataUpdate'))
			->createEvent('onBeforeModelDataSave', array($this, 'onBeforeModelDataSave'))
			->createEvent('onAfterModelDataSave', array($this, 'onAfterModelDataSave'))
			->createEvent('onModelFind', array($this, 'onModelFind'))
			->createEvent('onModelLoad', array($this, 'onModelLoad'))
			->createEvent('onModelLoadRandom', array($this, 'onModelLoadRandom'))
			->createEvent('onModelSave', array($this, 'onModelSave'))
			->createEvent('onModelDelete', array($this, 'onModelDelete'));

		$this->m_defaultFields = $this->m_fields;
		$this->m_defaultAliases = $this->m_aliases;

		return $this->setPrimaryFields();
	}

	/**
	 * Sets proper field name (for some specific types)
	 * @param string &$name
	 * @return bool
	 **/
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

	/**
	 * Sets value to model field
	 * @param string $name
	 * @param mixed $value
	 **/
	public function __set($name, $value)
	{
		if (!$this->setProperFieldName($name))
			return false;

		if (!$name || !isset($this->m_fields[$name]))
			return false;

		$this->validateOnSet($name, $value, 'values');

		return true;
	}

	private function validateCoreType($field, &$value, $getter = false)
	{
		$type = $this->getFieldType($field);
		$settings = $this->getFieldSettings($field);

		switch ($type)
		{
			case 'Id':
				break;
			case 'UnixTimestamp':
				$this->c('Db\Fields\\' . $type)->validate($value, $settings, $getter, $this);
				break;
		}

		return $this;
	}

	private function validateOnSet($name, &$value, $type = 'values')
	{
		if (!$name)
			return $this;

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

		$this->validateCoreType($name, $value, false);

		if ($type == 'values')
			$this->m_values[$name] = $value;
		else
			$this->m_data[$name] = $value;

		return $this;
	}

	/**
	 * Returns model field value
	 * @param string $name
	 * @return mixed
	 **/
	public function __get($name)
	{
		if (!$this->setProperFieldName($name))
		{
			// Check custom data
			if (!isset($this->m_customData[$name]))
				return false;

			return $this->m_customData[$name];
		}

		$value = null;

		if (isset($this->m_values[$name]))
			$value = $this->m_values[$name];
		elseif (isset($this->m_data[$name]))
			$value = $this->m_data[$name];

		// Validate (if it's a Field)
		$this->validateCoreType($name, $value, true);

		return $value;
	}

	private function setFieldValue($field, $value, $type)
	{
		if (!isset($this->m_fields[$field]))
			return $this;

		$this->validateOnSet($field, $value, $type);

		return $this;
	}

	/**
	 * Sets model data from DB
	 * @param array $data
	 * @return Model_Db_Component
	 **/
	public function setData($data)
	{
		$this->c('Events')->triggerEvent('onBeforeModelDataSave', array('model' => $this, 'modelData' => $data), $this);

		$old_data = $this->m_data;

		foreach ($data as $k => $v)
		{
			$this->setFieldValue($k, $v, 'data'); // For validation purposes
		}
//		$this->m_data = $data;

		$this->c('Events')->triggerEvent('onModelDataUpdate', array(
			'model' => $this,
			'oldData' => $old_data,
			'newData' => $data
		), $this);

		$this->m_dataLoaded = true;
		$this->m_updatingData = true;

		$this->c('Events')->triggerEvent('onAfterModelDataSave', array('model' => $this, 'modelData' => $data), $this);

		return $this;
	}

	/**
	 * Returns field type
	 * @param string $name
	 * @throws \Exceptions\ModelCrash
	 * @return string
	 **/
	public function getFieldType($name)
	{
		if (!isset($this->m_fields[$name]))
			throw new \Exceptions\ModelCrash('field "' . $name . '" was not found');

		if (is_array($this->m_fields[$name]) && isset($this->m_fields[$name]['type']))
			return $this->m_fields[$name]['type'];
		elseif ($this->m_fields[$name])
			return $this->m_fields[$name];
		else
			throw new \Exceptions\ModelCrash('unable to find "' . $name . '" field\'s type');
	}

	/**
	 * Returns field settings
	 * @param string $name
	 * @throws \Exceptions\ModelCrash
	 * @return array
	 **/
	public function getFieldSettings($name)
	{
		if (!isset($this->m_fields[$name]))
			throw new \Exceptions\ModelCrash('field "' . $name . '" was not found');

		if (!is_array($this->m_fields[$name]))
			return false;

		return $this->m_fields[$name];
	}

	/**
	 * Sets primary fields (index PRIMARY)
	 * @return Model_Db_Component
	 **/
	private function setPrimaryFields()
	{
		foreach ($this->m_fields as $field => $type)
			if (is_string($type) && $type == 'Id')
				$this->m_primaryFields[] = $field;

		$this->m_primaryFieldsCount = sizeof($this->m_primaryFields);

		return $this;
	}

	/**
	 * Validates field value by it's type
	 * @param string $name
	 * @param mixed $value
	 * @return mixed
	 **/
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

	/**
	 * Returns values of changed fields
	 * @return string
	 **/
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

	/**
	 * Returns changed fields names
	 * @return string
	 **/
	private function getChangedFields()
	{
		if (!$this->m_values)
			return '';

		$fields = '';

		foreach ($this->m_values as $field => $value)
			$fields .= '`' . $field . '`, ';

		return substr($fields, 0, (strlen($fields) - 2));
	}

	/**
	 * Returns fields names required for SQL query
	 * @return string
	 **/
	private function getFieldsForSql()
	{
		if (!$this->m_fields)
			return '';

		$fields = '';

		foreach ($this->m_fields as $field => $type)
			$fields .= '`' . $field . '`, ';

		return substr($fields, 0, (strlen($fields) - 2));
	}

	/**
	 * Returns primary fields values
	 * @return string
	 **/
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

	/**
	 * Generates SQL for saving
	 * @return Model_Db_Component
	 **/
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
		{
			$this->m_returnInsertId = true;
			$this->m_rawSql = 'INSERT INTO `' . $this->getTable() . '` (' . $this->getChangedFields() . ') VALUES (' . $this->getChangedFieldsValues() . ')';
		}

		return $this;
	}

	/**
	 * Generates SQL for loading
	 * @return Model_Db_Component
	 **/
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

	/**
	 * Generates SQL for deleting
	 * @return Model_Db_Component
	 **/
	private function generateDeleteSql()
	{
		if (!$this->m_data && !$this->m_values)
			return $this;

		$this->m_sqlData['params'] = array();
		$this->m_rawSql = 'DELETE FROM `' . $this->getTable() . '` WHERE ' . $this->getPrimaryFieldsValues() . ' LIMIT 1';

		return $this;
	}

	/**
	 * Performs SQL query
	 * @param bool $load = false
	 * @return Model_Db_Component
	 **/
	private function performSql($load = false)
	{
		if (!$this->m_rawSql)
			return $this;

		$result = true;
		$params = isset($this->m_sqlData['params']) ? $this->m_sqlData['params'] : array();

		if ($load)
		{
			$result = $this->c('Db')->getDb($this->getType())->selectWithParams($this->m_rawSql, $params)->getData();

			$this->c('Events')->triggerEvent('onModelDataSelection', array(
				'model' => $this,
				'sql' => $this->m_rawSql,
				'sqlParams' => $params
			), $this);

			if (isset($result[0]) && $result[0])
				$this->setData($result[0]);
		}
		else
		{
			$this->c('Db')->getDb($this->getType())->executeWithParams($this->m_rawSql, $params);

			if ($this->m_returnInsertId)
				$this->m_lastInsertId = $this->c('Db')->getDb($this->getType())->getInsertId();
		}

		return $this;
	}

	/**
	 * Returns model name
	 * @return string
	 **/
	public function getName()
	{
		return $this->m_model;
	}

	/**
	 * Returns table name
	 * @return string
	 **/
	public function getTable()
	{
		return $this->m_table;
	}

	/**
	 * Returns DB type of model
	 * @return string
	 **/
	public function getType()
	{
		return $this->m_dbType;
	}

	/**
	 * Returns model fields
	 * @return array
	 **/
	public function getFields()
	{
		return $this->m_fields;
	}

	/**
	 * Returns model fields' aliases
	 * @return array
	 **/
	public function getAliases()
	{
		return $this->m_aliases;
	}

	/**
	 * Returns model fields' types
	 * @return array
	 **/
	public function getFieldTypes()
	{
		return $this->m_fieldTypes;
	}

	/**
	 * Returns last insert ID
	 * @return int
	 **/
	public function getInsertId()
	{
		return $this->m_lastInsertId;
	}

	/**
	 * Restores fields to it's default values
	 * @return Model_Db_Component
	 **/
	public function restoreFields()
	{
		$this->m_fields = $this->m_defaultFields;

		return $this;
	}

	/**
	 * Restores aliases to it's default values
	 * @return Model_Db_Component
	 **/
	public function restoreAliases()
	{
		$this->m_aliases = $this->m_defaultAliases;

		return $this;
	}

	/**
	 * Updates fields with new data
	 * @return Model_Db_Component
	 **/
	protected function updateFields()
	{
		if (!$this->m_values)
			return $this;

		foreach ($this->m_values as $f => $v)
			$this->m_data[$f] = $v;

		return $this;
	}

	/**
	 * Loads row from DB by primary fields' values
	 * Example:  ->load(array('id' => 5));
	 * @param array $primaryfieldsValues
	 * @param bool $type
	 * @return Model_Db_Component
	 **/
	public function load($primaryfieldsValues, $type = false)
	{
		$this->m_sqlData['pf'] = array(
			'values' => $primaryfieldsValues
		);

		$this->generateLoadSql()
			->performSql(true);

		if ($type && method_exists($this, 'loadType' . $type))
			call_user_func(array($this, 'loadType' . $type));

		$this->c('Events')->triggerEvent('onModelFind', array(
			'model' => $this,
			'primaryFieldsValues' => $primaryfieldsValues,
			'loadType' => $type
		), $this);

		return $this;
	}

	/**
	 * Loads random row from DB
	 * @param string $type = ''
	 * @return Model_Db_Component
	 **/
	public function loadRandom($type = false)
	{
		$this->m_sqlData['random'] = true;

		$this->generateLoadSql()
			->performSql(true);

		if ($type && method_exists($this, 'loadType' . $type))
			call_user_func(array($this, 'loadType' . $type));

		$this->c('Events')->triggerEvent('onModelLoadRandom', array(
			'model' => $this,
			'loadType' => $type
		), $this);

		return $this;
	}

	/**
	 * Finds row in DB by some condition
	 * Example: ->find('`id` = :id AND (`name` LIKE \'%:name%\' OR `login` LIKE \'%:name%\')', array('id' => 5, 'name' => 'Shadez'));
	 * @param string $condition
	 * @param array $values
	 * @param string $type = ''
	 * @return Model_Db_Component
	 **/
	public function find($condition, $values, $type = false)
	{
		$this->m_sqlData['where'] = array(
			'condition' => $condition,
			'params' => $values
		);

		$this->generateLoadSql()
			->performSql(true);

		if ($type && method_exists($this, 'loadType' . $type))
			call_user_func(array($this, 'loadType' . $type));

		$this->c('Events')->triggerEvent('onModelFind', array(
			'model' => $this,
			'condition' => $condition,
			'values' => $values,
			'loadType' => $type
		), $this);

		return $this;
	}

	/**
	 * Saves changes to DB
	 * @return Model_Db_Component
	 **/
	public function save()
	{
		if (!$this->m_values)
			return $this; // No changes were made

		$values = $this->m_values;

		$this->generateSaveSql()
			->updateFields()
			->performSql();

		$this->c('Events')->triggerEvent('onModelSave', array(
			'model' => $this,
			'values' => $values
		), $this);

		return $this;
	}

	/**
	 * Deletes row from DB
	 * @return Model_Db_Component
	 **/
	public function delete()
	{
		if (!$this->m_data)
			return $this; // No data

		$data = $this->m_data;

		$this->generateDeleteSql()
			->performSql();

		$this->c('Events')->triggerEvent('onModelDelete', array(
			'model' => $this,
			'data' => $data
		), $this);

		return $this;
	}

	/**
	 * Checks if data was loaded
	 * @return bool
	 **/
	public function hasData()
	{
		return $this->m_dataLoaded;
	}

	/**
	 * Returns model data
	 * @return array
	 **/
	public function getData()
	{
		return $this->m_data;
	}

	public function additionalData($data)
	{
		if (!$data)
			return $this;

		foreach ($data as $key => $value)
		{
			// Do not overwrite existing data

			if (isset($this->m_customData[$key]))
				continue;

			$this->m_customData[$key] = $value;
		}

		return $this;
	}
};