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

class Block extends Component
{
	private $m_blockName = '';
	private $m_blockRegionName = '';
	private $m_blockVars = array();
	private $m_blockTemplate = '';
	private $m_blockTemplatePath = '';
	private $m_blockState = false;
	private $m_blockHtml = '';

	public function initialize()
	{
		$this->m_blockState = false;

		return $this;
	}

	/**
	 * Returns block state
	 * @return bool
	 **/
	public function getBlockState()
	{
		return $this->m_blockState;
	}

	/**
	 * Sets block name
	 * @param string $name
	 * @return Block_Component
	 **/
	public function setName($name)
	{
		$this->m_blockName = $name;

		return $this;
	}

	/**
	 * Returns block name
	 * @return string
	 **/
	public function getName()
	{
		return $this->m_blockName;
	}

	/**
	 * Sets block region
	 * @param string $name
	 * @return Block_Component
	 **/
	public function setRegion($name)
	{
		$this->m_blockRegionName = $name;

		if (!$this->c('View')->regionExists($name))
		{
			$region = $this->i('Region')->setName($name);
			$this->c('View')->addRegion($region);
		}

		$this->c('View')->getRegion($name)->addBlock($this);

		return $this;
	}

	/**
	 * Retuns block region
	 * @return Region_Component
	 **/
	public function getRegion()
	{
		return $this->c('View')->getRegion($this->getRegionName());
	}

	/**
	 * Returns block's region name
	 * @return string
	 **/
	public function getRegionName()
	{
		return $this->m_blockRegionName;
	}

	/**
	 * Sets block template
	 * @param string $name
	 * @param string $path = 'default'
	 * @return Block_Component
	 **/
	public function setTemplate($name, $path = 'default')
	{
		$path = str_replace('.', DS, $path);

		$this->m_blockTemplate = $name;
		$this->m_blockTemplatePath = $path;

		return $this;
	}

	/**
	 * Returns block's template name
	 * @param bool $withPath = false
	 * @return string
	 **/
	public function getTemplate($withPath = false)
	{
		return ($withPath ? $this->m_blockTemplatePath . DS : '') . $this->m_blockTemplate . '.' . TPL_EXT;
	}

	/**
	 * Sets block variable
	 * @param string $name
	 * @param string $value
	 * @return Block_Component
	 **/
	public function setVar($name, $value)
	{
		$this->m_blockVars[$name] = $value;

		return $this;
	}

	/**
	 * Returns block's variable value
	 * @param string $name
	 * @return mixed
	 **/
	public function getVar($name)
	{
		return isset($this->m_blockVars[$name]) ? $this->m_blockVars[$name] : null;
	}

	/**
	 * Performs block rendering operations
	 * @param array $vars
	 * @throws \Exceptions\BlockCrash
	 * @return Block_Component
	 **/
	public function renderBlock($vars)
	{
		if ($this->m_blockState)
			return $this;

		$template = TEMPLATES_DIR . $this->m_blockTemplatePath . DS . $this->m_blockTemplate . '.' . TPL_EXT;

		if (!file_exists($template))
			throw new \Exceptions\BlockCrash('unable to build block "' . $this->getName() . '": template was not found');

		// Vars priority: block/controller/core

		if ($vars['core'])
			extract($vars['core'], EXTR_SKIP);

		if ($vars['controller'])
			extract($vars['controller'], EXTR_SKIP);

		if ($this->m_blockVars)
			extract($this->m_blockVars, EXTR_SKIP);

		ob_start();

		require_once($template);

		$this->m_blockHtml = ob_get_contents();

		ob_clean();

		$this->m_blockState = true;

		return $this;
	}

	/**
	 * Sets block's HTML content
	 * @return string
	 **/
	public function getBlockHTML()
	{
		return $this->m_blockHtml;
	}

	/**
	 * Returns HTML content for specific region
	 * @param string $name
	 * @return string
	 **/
	protected function getRegionContents($name)
	{
		return $this->c('View')->getRegionContents($name);
	}
};