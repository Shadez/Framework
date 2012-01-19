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

class Block_Component extends Component
{
	private $m_blockName = '';
	private $m_blockVariables = array();
	private $m_blockTemplateName = '';
	private $m_blockTemplateDir = '';
	private $m_blockRegion = '';
	private $m_builded = false;
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
			$this->c('Log')->writeError('%s : unable to find template "%s.ctp" for block %s (hash: %s)!', __METHOD__, $this->m_blockTemplateDir . DS . $this->m_blockTemplateName, $this->getBlockName(), $this->m_uniqueHash);
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

		require_once($template);
		$this->c('Page')->addContents(ob_get_contents(), $this->m_blockRegion);

		ob_clean();

		// Cleanup variables
		if ($core_vars)
		{
			foreach ($core_vars as $varName => &$varValue)
			{
				unset($$varName);
				$varName = null;
				$varValue = null;
			}
		}

		unset($varName, $varValue);

		if ($this->m_blockVariables)
		{
			foreach ($this->m_blockVariables as $varName => &$varValue)
				unset($$varName);
		}

		unset($varName, $varValue);

		return $this;
	}
}