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

class View extends Component
{
	private $m_regions = array();
	private $m_tplVars = array();
	private $m_htmlContents = array();

	public function initialize()
	{
		$this->setAllVars();

		return $this;
	}

	/**
	 * Renders all regions
	 * @return View_Component
	 **/
	private function renderRegions()
	{
		foreach ($this->m_regions as $region)
			$this->m_htmlContents[$region->getName()] = $region->renderRegion($this->m_tplVars)->getRegionHTML();

		return $this;
	}

	/**
	 * Sets all variables to template (from core & controller)
	 * @return View_Component
	 **/
	private function setAllVars()
	{
		$this->m_tplVars['core'] = $this->getCore()->getVars();
		$this->m_tplVars['controller'] = $this->getCore()->getActiveController()->getVars();

		return $this;
	}

	/**
	 * Builds all provided blocks and blocks of current controller's group
	 * @param array $blocks = array()
	 * @throws \Exceptions\ViewCrash
	 * @return View_Component
	 **/
	public function buildBlocks($blocks = array())
	{
		$controller = $this->getCore()->getActiveController();

		if (!$controller)
			throw new \Exceptions\ViewCrash('unable to create controller blocks: controller does not exists');

		if ($blocks)
		{
			if (is_string($blocks))
				$blocks = array($blocks);

			foreach ($blocks as $name)
				if (method_exists($controller, 'block_' . $name))
					$block = call_user_func(array($controller, 'block_' . $name));

			unset($block, $name);
		}

		// Check & build controller group's blocks (if exists)
		$groupBlocks = $this->c('ControllerGroups')->getBlocksFromGroup($controller->getControllerGroup());

		if ($groupBlocks)
		{
			foreach ($groupBlocks as $name => $block)
			{
				if (!$block || !isset($block['template']) || !isset($block['region']))
					continue;

				$bl = $this->i('Block')
					->setTemplate($block['template'][0], $block['template'][1])
					->setRegion($block['region'])
					->setName($name);

				if (isset($block['vars']) && $block['vars'])
				{
					foreach ($block['vars'] as $var => $value)
						$bl->setVar($var, $value);
				}
			}
		}

		return $this;
	}

	/**
	 * Includes template file
	 * @param string $tpl_name
	 * @param string $tpl_path = 'default'
	 * @throws \Exceptions\ViewCrash
	 * @return View_Component
	 **/
	public function displayTemplate($tpl_name, $tpl_path = 'default')
	{
		$tpl_path = str_replace('.', DS, $tpl_path);

		$tpl = TEMPLATES_DIR . $tpl_path . DS . $tpl_name . '.' . TPL_EXT;

		if (!file_exists($tpl))
			throw new \Exceptions\ViewCrash('unable to find template file (' . $tpl_name . ')');

		require_once($tpl);

		return $this;
	}

	/**
	 * Adds region
	 * @param Region $region
	 * @return View_Component
	 **/
	public function addRegion(Region $region)
	{
		$this->m_regions[$region->getName()] = $region;

		return $this;
	}

	/**
	 * Checks if region exists
	 * @param string $name
	 * @return bool
	 **/
	public function regionExists($name)
	{
		return isset($this->m_regions[$name]);
	}

	/**
	 * Returns Region_Component instance by region name
	 * @param mixed $region
	 * @throws \Exceptions\ViewCrash
	 * @return Region_Component
	 **/
	public function getRegion($region)
	{
		if (is_object($region) && isset($this->m_regions[$region->getName()]))
			return $this->m_regions[$region->getName()];
		elseif (is_string($region) && isset($this->m_regions[$region]))
			return $this->m_regions[$region];
		else
			throw new \Exceptions\ViewCrash('region ' . (is_object($region) ? $region->getName() : $region) . ' was not found');

		return null;
	}

	/**
	 * Returns region contents
	 * @param string $name
	 * @return string
	 **/
	public function getRegionContents($name)
	{
		return isset($this->m_htmlContents[$name]) ? $this->m_htmlContents[$name] : '';
	}

	/**
	 * Builds page
	 * @param string $layoutFile = ''
	 * @param string $layoutPath = ''
	 * @throws \Exceptions\ViewCrash
	 * @return View_Component
	 **/
	public function buildPage($layoutFile = false, $layoutPath = false)
	{
		if (!$layoutFile || !$layoutPath)
		{
			$controller = $this->getCore()->getActiveController();
			$defaultLayout = $this->c('Config')->getValue('controller.default_layout');

			if (!$controller)
				throw new \Exceptions\ViewCrash('unable to build page: active controller does not exists');

			// Check controller group
			$group = $controller->getControllerGroup();

			if ($group)
			{
				$layout = $this->c('ControllerGroups')->getGroupLayout($group);

				if ($layout)
				{
					$layoutFile = isset($layout[0]) ? $layout[0] : false;
					$layoutPath = isset($layout[1]) ? $layout[1] : false;
				}
			}

			if (!$layoutFile)
				$layoutFile = isset($defaultLayout[0]) ? $defaultLayout[0] : false;

			if (!$layoutPath)
				$layoutPath = isset($defaultLayout[1]) ? $defaultLayout[1] : false;
		}

		if (!$layoutFile || !$layoutPath)
			throw new \Exceptions\ViewCrash('layout file was not defined');

		$path = str_replace('.', DS, $layoutPath);

		if ($this->getCore()->getActiveController()->isAjaxPage())
			$layoutFile .= '_ajax';

		$tpl = TEMPLATES_DIR . $path . DS . $layoutFile . '.' . TPL_EXT;

		if (!file_exists($tpl))
			throw new \Exceptions\ViewCrash('unable to find layout file (' . $layoutPath . '.' . $layoutFile . ')');

		$this->renderRegions();

		$this->c('Layout')->loadClientFiles();

		if ($this->m_tplVars['core'])
			extract($this->m_tplVars['core'], EXTR_SKIP);

		if ($this->m_tplVars['controller'])
			extract($this->m_tplVars['controller'], EXTR_SKIP);
		
		require_once($tpl);

		return $this;
	}
};