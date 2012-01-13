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

class QueryResult_Db_Component extends Component
{
	protected $m_sqlData = array();
	protected $m_idField = '';
	protected $m_sqlBuilder = null;
	protected $m_model = null;

	public function clear()
	{
		$this->m_sqlData = array();

		if ($this->m_sqlBuilder)
			$this->m_sqlBuilder->clear();

		$this->m_model = null;

		return $this;
	}

	public function model($name)
	{
		$this->clear();

		$this->m_model = $this->i($name, 'Model');

		if (!$this->m_model)
			throw new ModelCrash_Exception_Component('Model ' . $name . ' was not found!');

		if (!$this->m_sqlBuilder)
			$this->m_sqlBuilder = new Query_Component('Query', $this->core);

		$this->m_sqlBuilder->setModel($name);

		return $this;
	}

	public function setItemId($id)
	{
		return $this->setModelIdField()
			->setId($id);
	}

	protected function setModelIdField()
	{
		foreach ($this->m_model->m_fields as $field => $type)
			if ($type === 'Id')
				$this->m_idField = $field;

		if (!$this->m_idField)
			$this->m_idField = 'id';

		return $this;
	}

	protected function setId($id)
	{
		if (!$this->m_idField)
			return $this;

		if (is_array($id))
			$this->m_sqlBuilder->fieldCondition(
				$this->m_model->m_table . '.' . $this->m_idField, ' IN (' . $this->arrayToString($id) . ')'
			);
		else
			$this->m_sqlBuilder->fieldCondition(
				$this->m_model->m_table . '.' . $this->m_idField, ' = ' . $id
			);

		return $this;
	}

	private function arrayToString(&$values)
	{
		$str = '';
		$size = sizeof($values);
		for ($i = 0; $i < $size; ++$i)
			if ($i)
				$str .= ', \'' . addslashes(urldecode($values[$i])) . '\'';
			else
				$str .= '\'' . addslashes(urldecode($values[$i])) . '\'';

		return $str;
	}

	public function fields($fields)
	{
		$this->m_sqlBuilder->setFields($fields);

		return $this;
	}

	public function keyIndex($field)
	{
		$this->m_sqlBuilder->keyIndex($field);

		return $this;
	}

	public function loadItem()
	{
		$item = $this->c('SqlQuery', 'Db')->selectItem($this->m_sqlBuilder->getSql(), $this->m_model->m_dbType, $this->m_sqlBuilder->getIndexKey())->getData();

		$fields = $this->m_sqlBuilder->getLocaleFields();

		if (!$item)
			return false;

		if (!$fields)
		{
			$holder = $this->i('ResultHolder', 'Db')
				->setResult(array($item), $this->m_sqlBuilder->getSql());

			unset($item);

			return $holder; // Nothing to do
		}

		// Parse fields
		$this->parseResults($item, $fields);

		$holder = $this->i('ResultHolder', 'Db')
			->setResult(array($item), $this->m_sqlBuilder->getSql());

		unset($item);

		return $holder;
	}

	protected function parseResults(&$item, &$fields)
	{
		if (!$item || !$fields)
			return $this;

		foreach ($fields as $table )
		{
			foreach ($table as $field)
			{
				if (isset($item[$field['temp']]))
				{
					// Is it filled with any data?
					if ($item[$field['temp']] != null)
					{
						$item[$field['alias']] = $item[$field['temp']];
						unset($item[$field['temp']]);
						continue;
					}
					// Is default field not empty?
					if (isset($item[$field['alias']]) && $item[$field['alias']] != null)
						unset($item[$field['temp']]);
				}
			}
		}

		$item = (object) $item;

		return $this;
	}

	public function loadItems()
	{
		$items = $this->c('SqlQuery', 'Db')->selectItems($this->m_sqlBuilder->getSql(), $this->m_model->m_dbType, $this->m_sqlBuilder->getIndexKey())->getData();

		$fields = $this->m_sqlBuilder->getLocaleFields();

		if (!$items)
			return false;

		if (!$fields)
		{
			$holder = $this->i('ResultHolder', 'Db')
				->setResult($items, $this->m_sqlBuilder->getSql());

			unset($items);

			return $holder; // Nothing to do
		}

		// Parse fields
		foreach ($items as &$item)
			$this->parseResults($item, $fields);

		$holder = $this->i('ResultHolder', 'Db')
			->setResult($items, $this->m_sqlBuilder->getSql());

		unset($items);

		return $holder;
	}

	public function addModel($model_name)
	{
		$this->m_sqlBuilder->addModel($model_name);

		return $this;
	}

	public function join($type, $model, $join, $field_parent, $field_child, $custom_values = array())
	{
		$this->m_sqlBuilder->join($type, $model, $join, $field_parent, $field_child, $custom_values);

		return $this;
	}

	public function order($fields, $direct = 'asc')
	{
		$this->m_sqlBuilder->order($fields, $direct);

		return $this;
	}

	public function limit($limit, $offset = 0)
	{
		$this->m_sqlBuilder->limit($limit, $offset);

		return $this;
	}

	public function fieldCondition($field, $condition, $next = 'AND', $binary = false)
	{
		$this->m_sqlBuilder->fieldCondition($field, $condition, $next, $binary);

		return $this;
	}

	public function fieldsConditions($conditions)
	{
		$this->m_sqlBuilder->fieldsConditions($conditions);

		return $this;
	}

	public function runFunction($function, $field, $fieldAlias = '')
	{
		$this->m_sqlBuilder->runFunction($function, $field, $fieldAlias);

		return $this;
	}

	public function setAlias($model_name, $field_name, $alias)
	{
		$this->m_sqlBuilder->setAlias($model_name, $field_name, $alias);

		return $this;
	}

	public function group($model_name, $field_name)
	{
		$this->m_sqlBuilder->group($model_name, $field_name);

		return $this;
	}
}