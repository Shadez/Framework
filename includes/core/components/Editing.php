<?php

/**
 * Copyright (C) 2009-2011 Shadez <https://github.com/Shadez>
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

class Editing_Component extends Component
{
	protected $m_model = null;
	protected $m_fields = array();
	protected $m_insertType = '';
	protected $m_deleteBeforeInsert = false;
	protected $m_rawSql = '';
	protected $m_primaryField = '';
	protected $m_id = 0;
	protected $m_limit = 0;
	protected $m_data = array();
	protected $m_insertId = 0;

	public function __set($name, $value)
	{
		$use = '';
		$type = '';

		if (!$this->g($name, $use, $type))
			return false;

		switch($type)
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
			default:
				break;
		}

		$this->m_fields[$use] = $value;

		return true;
	}

	public function __get($name)
	{
		$use = '';
		$type = '';

		if (!$this->g($name, $use, $type))
			return false;

		$value = '';

		if (isset($this->m_data[$use]) && isset($this->m_fields[$use]))
		{
			if ($this->m_data[$use] == $this->m_fields[$use])
				$value = $this->m_data[$use];
			elseif ($this->m_fields[$use] && !$this->m_data[$use])
				$value = $this->m_fields[$use]; // __set() changes value of m_fields[$field] field.
			elseif (!$this->m_fields[$use] && $this->m_data[$use])
				$value = $this->m_data[$use];
		}
		elseif (isset($this->m_data[$use]))
			$value = $this->m_data[$use];
		elseif (isset($this->m_fields[$use]))
			$value = $this->m_fields[$use];

		switch($type)
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
		}

		return $value;
	}

	protected function g(&$passedName, &$use, &$type)
	{
		$checker = 0;
		$field = $this->getAppropriateFieldName($passedName, $checker);

		// Since we can assign values only to existing fields, need to check field existence too
		if (!$this->m_model || !isset($this->m_fields[$field]))
			return false;

		$type = '';

		if ($checker == 1)
		{
			$use = $field;
			$type = $this->m_model->m_fields[$passedName];
		}
		else
		{
			$use = $passedName;
			$type = $this->m_model->m_fields[$this->getModelFieldName($passedName)];
		}

		return true;
	}

	protected function getModelFieldName($name)
	{
		if (strpos($name, '_loc') !== false)
		{
			$n = explode('_loc', $name);
			return $n[0];
		}

		// Check locale

		$locales = array('de', 'en', 'es', 'fr', 'ru');

		foreach ($locales as $loc)
		{
			if (strpos($name, '_' . $loc) !== false)
			{
				$n = explode('_' . $loc, $name);
				return $n[0];
			}
		}

		return $name;
	}

	protected function getAppropriateFieldName($field, &$checker)
	{
		if (!isset($this->m_fields[$field]) && (isset($this->m_model->m_fields[$field]) && is_string($this->m_model->m_fields[$field])))
		{
			// Locale or DbLocale field
			if (strtolower($this->m_model->m_fields[$field]) == 'locale')
			{
				$checker = 1;
				return $field . '_' . $this->c('Locale')->GetLocale();
			}
			elseif (strtolower($this->m_model->m_fields[$field]) == 'dblocale' && $this->c('Locale')->GetLocaleID() != LOCALE_EN)
			{
				$checker = 1;
				return $field . '_' . $this->c('Locale')->GetLocaleID();
			}
			else
				return $field;
		}

		return $field;
	}

	public function clearValues()
	{
		$this->m_data = array();
		$this->m_deleteBeforeInsert = false;
		$this->m_fields = array();
		$this->m_id = 0;
		$this->m_insertType = '';
		$this->m_limit = 0;
		$this->m_model = null;
		$this->m_primaryField = '';
		$this->m_rawSql = '';
		$this->m_insertId = 0;

		return $this;
	}

	public function setModel($modelName)
	{
		$this->clearValues()
			->m_model = $this->i($modelName, 'Model');

		if (!$this->m_model)
			throw new ModelCrash_Exception_Component('Model ' . $modelName . ' was not found!');

		return $this->convertFields();
	}

	public function setType($type, $deleteBeforeInsert = false)
	{
		if (!in_array($type, array('update', 'insert')))
			return $this;

		$this->m_insertType = $type;
		$this->m_deleteBeforeInsert = $deleteBeforeInsert;

		return $this;
	}

	public function setId($id)
	{
		$this->m_id = $id;

		return $this;
	}

	protected function convertFields()
	{
		if (!$this->m_model)
			return $this;

		$fields = array();

		foreach ($this->m_model->m_fields as $field => $type)
		{
			if (is_array($type) && isset($type['type']))
				switch (strtolower($type['type']))
				{
					case 'string':
						$fields[$field] = '';
						break;
					case 'integer':
						$fields[$field] = 0;
						break;
					default:
						$fields[$field] = null;
				}
			elseif (is_string($type))
			{
				switch (strtolower($type))
				{
					case 'locale':
						$locales = array('de', 'en', 'es', 'fr', 'ru');
						foreach ($locales as $locale)
							$fields[$field . '_' . $locale] = '';
						break;
					case 'dblocale':
						for ($i = 1; $i < 9; ++$i)
							$fields[$field . '_loc' . $i] = '';
						break;
					case 'id':
						$fields[$field] = 0;
						$this->m_primaryField = $field;
						break;
					default:
						$fields[$field] = null;
				}
			}
			else
				throw new ModelCrash_Exception_Component('Unknown field type for model ' . $this->m_model->m_model . ' (field: ' . $field . ')!');
		}

		$this->m_fields = $fields;
		unset($fields);

		return $this;
	}

	public function save()
	{
		$this->parseSql();

		$this->c('Db')->{$this->m_model->m_dbType}()->query($this->m_rawSql);

		if ($this->m_insertType == 'insert')
			$this->m_insertId = $this->c('Db')->{$this->m_model->m_dbType}()->GetInsertID();

		return $this;
	}

	public function getInsertId()
	{
		return $this->m_insertId;
	}

	public function load()
	{
		$this->m_data = $this->i('QueryResult', 'Db')
			->model($this->m_model->m_model)
			->setItemId($this->m_id)
			->loadItem();

		return $this;
	}

	public function delete()
	{
		$this->c('Db')->{$this->m_model->m_dbType}()->query("DELETE FROM `%s` WHERE `%s` = '%s'", $this->m_model->m_table, $this->getPrimaryField(), $this->m_id);

		return $this;
	}

	protected function getChangedFieldsValues()
	{
		if (!$this->m_fields)
			return 'NULL = NULL';

		$sql = '';

		foreach ($this->m_fields as $field => $value)
		{
			if ($value)
				$sql .= '`' . $field . '` = \'' . addslashes($value) . '\',';
		}

		return substr($sql, 0, (strlen($sql) - 1));
	}

	protected function getFields()
	{
		if (!$this->m_fields)
			return '';

		$sql = '';
		foreach ($this->m_fields as $field => $val)
			$sql .= ' `' . $field . '`,';

		return substr($sql, 0, (strlen($sql) - 1));
	}

	protected function getFieldsValues()
	{
		if (!$this->m_fields)
			return '';

		$sql = '';
		foreach ($this->m_fields as $val)
			$sql .= '\'' . addslashes($val) . '\',';

		return substr($sql, 0, (strlen($sql) - 1));
	}

	private function parseSql()
	{
		$this->m_rawSql = '';

		if ($this->m_insertType == 'update')
		{
			$this->m_rawSql .= 'UPDATE `' . $this->m_model->m_table . '` SET ';
			$this->m_rawSql .= $this->getChangedFieldsValues();
			$this->m_rawSql .= ' WHERE `' . $this->getPrimaryField() . '` = ' . $this->m_id;
			if ($this->m_limit > 0)
				$this->m_rawSql .= ' LIMIT ' . (is_array($this->m_limit) ? $this->m_limit[0] . ', ' . $this->m_limit[1] : $this->m_limit);
		}
		elseif ($this->m_insertType == 'insert')
		{
			if ($this->m_deleteBeforeInsert)
				$this->c('Db')->{$this->m_model->m_dbType}()->query("DELETE FROM `%s` WHERE `%s` = '%s'", $this->m_model->m_table, $this->getPrimaryField(), addslashes($this->m_id));

			$this->m_rawSql .= 'INSERT INTO `' . $this->m_model->m_table . '` (' . $this->getFields() . ') VALUES (' . $this->getFieldsValues() . ')';
		}
		else
			return $this;

		return $this;
	}

	protected function getPrimaryField()
	{
		if ($this->m_primaryField)
			return $this->m_primaryField;

		if (!$this->m_model)
			return false;

		$primaryField = '';

		foreach ($this->m_model->m_fields as $field => $type)
			if (is_string($type) && strtolower($type) == 'id')
				$type = $field;

		$this->m_primaryField = $primaryField;

		return $this->m_primaryField;
	}
}