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

class Block_Component extends Component
{
	private $m_blockName = '';
	private $m_blockVariables = array();
	private $m_blockTemplateName = '';
	private $m_blockTemplateDir = '';
	private $m_blockRegion = '';
	private $m_blockType = '';
	private $m_blockUnit = null;
	private $m_blockUnits = array();
	private $m_blockUnitObject = null;
	protected $m_model = null;
	protected $m_contents = '';
	private $m_builded = false;
	protected $m_unitData = array();
	private $m_rendered = false;

	/**
	 * Sets new block name
	 * @access public
	 * @param  string $name
	 * @return Block_Component
	 **/
	public function setBlockName($name)
	{
		$this->m_blockName = $name;

		return $this;
	}

	/**
	 * Sets block type
	 * @access public
	 * @param  string $type
	 * @return Block_Component
	 **/
	public function setBlockType($type)
	{
		$this->m_blockType = $type;

		return $this;
	}
	/**
	 * Returns block name
	 * @access public
	 * @return string
	 **/
	public function getBlockName()
	{
		return $this->m_blockName;
	}

	/**
	 * Sets new block variable
	 * @access public
	 * @param  string $varName
	 * @param  mixed $varValue
	 * @return Block_Component
	 **/
	public function setVar($varName, $varValue)
	{
		$this->m_blockVariables[$varName] = $varValue;

		return $this;
	}

	/**
	 * Sets block variable valeu
	 * @access public
	 * @param  string $varName
	 * @return mixed
	 **/
	public function getVar($varName)
	{
		return isset($this->m_blockVariables[$varName]) ? $this->m_blockVariables[$varName] : false;
	}

	/**
	 * Sets template for current block
	 * @access public
	 * @param  string $templateName
	 * @param  string $templateDir = 'default'
	 * @return Block_Component
	 **/
	public function setTemplate($templateName, $templateDir = 'default')
	{
		if (!$templateName || !$templateDir || $this->m_blockTemplateName || $this->m_blockTemplateDir)
			return $this; // Wrong data or already defined

		$this->m_blockTemplateDir = $templateDir;
		$this->m_blockTemplateName = $templateName;

		return $this;
	}

	/**
	 * Sets region for current block
	 * @access public
	 * @param  string $regionName
	 * @return Block_Component
	 **/
	public function setRegion($regionName)
	{
		if (!$this->m_blockRegion)
			$this->m_blockRegion = ucfirst($regionName);
		else
			return $this;

		$this->c('Document')->getRegion($regionName)->addBlock($this);

		return $this;
	}

	/**
	 * Returns region name for current block
	 * @access public
	 * @return string
	 **/
	public function getRegionName()
	{
		return $this->m_blockRegion;
	}

	/**
	 * Returns region object for current block
	 * @access public
	 * @return Region_Component
	 **/
	public function getRegion()
	{
		if ($this->m_blockRegion)
			return $this->c('Document')->getRegion($this->m_blockRegion);

		return $this->c('Document')->getRegion('Main');
	}

	/**
	 * Actions that requied to be performed before block render
	 * @access protected
	 * @return Block_Component
	 **/
	protected function beforeRender()
	{
		return $this;
	}

	/**
	 * Actions that required to be performed after block render
	 * @access protected
	 * @return Block_Component
	 **/
	protected function afterRender()
	{
		return $this;
	}

	/**
	 * Sets block state
	 * @access public
	 * @param  bool $state
	 * @return Block_Component
	 **/
	public function setBlockState($state)
	{
		$this->m_builded = $state;

		return $this;
	}

	/**
	 * Returns block state
	 * @access public
	 * @return bool
	 **/
	public function getBlockState()
	{
		return $this->m_builded;
	}

	/**
	 * Performs block render
	 * @access public
	 * @return Block_Component
	 **/
	public function render()
	{
		if ($this->m_rendered)
			return $this;

		$this->m_rendered = true;

		$this->beforeRender();

		if (!$this->m_blockTemplateDir || !$this->m_blockTemplateName)
			return $this;

		$template = TEMPLATES_DIR . $this->m_blockTemplateDir . DS . $this->m_blockTemplateName . '.ctp';
		if (!file_exists($template))
		{
			$this->c('Log')->writeError('%s : unable to find template "%s.ctp" (hash: %s)!', __METHOD__, $this->m_blockTemplateDir . DS . $this->m_blockTemplateName, $this->m_uniqueHash);
			return $this;
		}

		$this->compile($template);

		return $this->afterRender();
	}

	private function compile($template)
	{
		$core_vars = $this->core->getCoreVars();

		if ($core_vars)
			extract($core_vars, EXTR_SKIP);

		if ($this->m_blockVariables)
			extract($this->m_blockVariables, EXTR_SKIP);

		ob_start();

		require($template);
		$this->c('Page')->addContents(ob_get_contents(), $this->m_blockRegion);

		ob_clean();

		return $this;
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
		$this->m_model = new $class_name($params['model'], $this->core);
		$this->m_model->findParams($params);

		return $this;
	}

	/**
	 * Set item ID to find
	 * @access public
	 * @param  mixed $id
	 * @return Block_Component
	 **/
	public function setItem($id)
	{
		if (!$this->m_model)
			return $this;

		$this->m_model->setItem($id);

		return $this;
	}

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

	public function setMainUnit($unit_name)
	{
		$this->m_blockUnit = $unit_name;
		$this->m_blockUnits[] = $unit_name;

		return $this;
	}

	public function getMainUnit()
	{
		return $this->m_blockUnit;
	}

	public function getAllUnits()
	{
		return $this->m_blockUnits;
	}

	public function setUnitData($data)
	{
		$this->m_unitData = $data;

		return $this;
	}

	public function setMainUnitObject($unit)
	{
		$this->m_blockUnitObject = $unit;

		return $this;
	}

	public function mainUnit()
	{
		return $this->m_blockUnitObject;
	}
}