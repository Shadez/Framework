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

class Block_Component extends Component
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

	public function getBlockState()
	{
		return $this->m_blockState;
	}

	public function setName($name)
	{
		$this->m_blockName = $name;

		return $this;
	}

	public function getName()
	{
		return $this->m_blockName;
	}

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

	public function getRegion()
	{
		return $this->c('View')->getRegion($this->getRegionName());
	}

	public function getRegionName()
	{
		return $this->m_blockRegionName;
	}

	public function setTemplate($name, $path = 'default')
	{
		$path = str_replace('.', DS, $path);

		$this->m_blockTemplate = $name;
		$this->m_blockTemplatePath = $path;

		return $this;
	}

	public function getTemplate($withPath = false)
	{
		return ($withPath ? $this->m_blockTemplatePath . DS : '') . $this->m_blockTemplate . '.' . TPL_EXT;
	}

	public function setVar($name, $value)
	{
		$this->m_blockVars[$name] = $value;

		return $this;
	}

	public function getVar($name)
	{
		return isset($this->m_blockVars[$name]) ? $this->m_blockVars[$name] : null;
	}

	public function renderBlock($vars)
	{
		if ($this->m_blockState)
			return $this;

		$template = TEMPLATES_DIR . $this->m_blockTemplatePath . DS . $this->m_blockTemplate . '.' . TPL_EXT;

		if (!file_exists($template))
			throw new BlockCrash_Exception_Component('unable to build block "' . $this->getName() . '": template was not found');

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

	public function getBlockHTML()
	{
		return $this->m_blockHtml;
	}

	protected function getRegionContents($name)
	{
		return $this->c('View')->getRegionContents($name);
	}
};