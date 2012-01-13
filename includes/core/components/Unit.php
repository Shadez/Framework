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

class Unit_Component extends Component
{
	protected $m_model = null;
	protected $m_unitData = array();
	protected $m_queryBuilder = null;
	protected $m_itemId = 0;
	protected $m_modelName = '';

	public function initialize()
	{
		if ($this->m_queryBuilder)
			return $this;

		$this->m_queryBuilder = new Query_Component('Query', $this->core);

		return $this;
	}

	public function findData()
	{
		return $this;
	}

	public function setModel($model_name)
	{
		$this->initialize();

		$this->m_queryBuilder->setModel($model_name);

		return $this;
	}

	public function addModel($model_name)
	{
		$this->m_queryBuilder->addModel($model_name);

		return $this;
	}

	public function join($type, $model, $join, $field_parent, $field_child, $custom_values = array())
	{
		$this->m_queryBuilder->join($type, $model, $join, $field_parent, $field_child, $custom_values);

		return $this;
	}

	public function order($fields, $direct = 'asc')
	{
		$this->m_queryBuilder->order($fields, $direct);

		return $this;
	}

	public function limit($limit, $offset = 0)
	{
		$this->m_queryBuilder->limit($limit, $offset);

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
		$this->m_queryBuilder->fieldCondition($field, $condition, $next, $binary);

		return $this;
	}

	public function fieldsConditions($conditions)
	{
		$this->m_queryBuilder->fieldsConditions($conditions);

		return $this;
	}

	public function setItem($id)
	{
		throw new DBCrash_Exception_Component('Unable to perform Unit::setItem($id)!');
	}

	/**
	 * Sets DB find parameters
	 * @access public
	 * @param  array $params
	 * @return Block_Component
	 **/
	public function setFind($params)
	{
		if (!isset($params['model']))
			return $this;

		$class_name = $params['model'] . '_Model_Component';
		$this->m_model = $this->c($params['model'], 'Model');

		if (!$this->m_model)
			throw new ModelCrash_Exception_Component('Model ' . $params['model'] . ' was not found!');

		$this->m_model->findParams($params);

		return $this;
	}

	/**
	 * Set item ID to find
	 * @access public
	 * @param  mixed $id
	 * @return Block_Component
	 **/
/*	public function setItem($id)
	{
		if (!$this->m_model)
			return $this;

		$this->m_model->setItem($id);

		return $this;
	}*/

	/**
	 * Sets item var to find
	 * @access public
	 * @param  string $var
	 * @return Block_Component
	 **/
	public function setItemVar($var)
	{
		if (!$this->m_model)
			return $this;

		$this->m_model->setItem(0, $var);

		return $this;
	}

	/**
	 * Sets search conditions
	 * @access public
	 * @param  array $conditions
	 * @return Block_Component
	 **/
	public function setFindWhere($conditions)
	{
		if (!$this->m_model)
			return $this;

		$this->m_model->setConditions($conditions);

		return $this;
	}


	/**
	 * Sets random item request
	 * @access public
	 * @return Block_Component
	 **/
	public function setItemRandom()
	{
		if (!$this->m_model)
			return $this;

		$this->m_model->setItemRandom();

		return $this;
	}

	/**
	 * Sets DB search conditions
	 * @access public
	 * @param  array $conditions
	 * @return Block_Component
	 **/
	public function setConditions($conditions)
	{
		if (!$this->m_model)
			return $this;

		$this->m_model->setConditions($conditions);

		return $this;
	}

	/**
	 * Sets join(s) conditions
	 * @access public
	 * @param  array $conditions
	 * @return Block_Component
	 **/
	public function setJoin($conditions)
	{
		if (!$this->m_model)
			return $this;

		$this->m_model->setJoin($conditions);

		return $this;
	}

	public function getData()
	{
		return $this->m_unitData;
	}

	public function issetData()
	{
		if ($this->m_unitData)
			return true;

		return false;
	}
}