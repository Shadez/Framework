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

class Controller_Component extends Component
{
	private $m_postBuild = array();
	private $m_blocks = array();
	private $m_units = array();
	protected $m_isDefaultController = false;
	protected $m_isAjax = false;
	protected $m_skipBuild = false;
	protected $m_dummy_page = false;
	protected $m_buildFailed = false;
	protected $m_menuIndex = null;
	protected $m_errorPage = false;
	protected $m_pageTitle = '';
	protected $m_clientFilesController = '';
	protected $m_installerController = false;

	protected $m_templates = array();

	protected $m_css = array();
	protected $m_js = array();

	public function initialize()
	{
		$this->core->setVar('l', $this->c('Locale'));
		$this->core->setVar('controller', $this);

		$this->setTemplates()
			->preparePage()
			->beforeActions()
			->initClientFiles()
			->registerClientFiles()
			->addClientFiles()
			->beforeBuild()
			->build($this->c('Core'))
			->pageTitle()
			->performPostBuild()
			->complete();

		return $this;
	}

	protected function initClientFiles()
	{
		if ($this->m_clientFilesController)
			$controller = $this->m_clientFilesController;
		else
			$controller = strtolower(substr($this->m_component, 0, strpos($this->m_component, '_')));

		$this->m_css = $this->c('Layout')->getControllerCss($controller);
		$this->m_js = $this->c('Layout')->getControllerJs($controller);

		return $this;
	}

	protected function beforeBuild()
	{
		return $this;
	}

	protected function preparePage()
	{
		if ($this->m_isAjax)
			header('Content-type: text/javascript');

		return $this;
	}

	protected function beforeActions()
	{
		return $this;
	}

	protected function pageTitle()
	{
		$this->c('Layout')->setPageTitle($this->m_pageTitle);
		return $this;
	}

	protected function registerClientFiles()
	{
		return $this;
	}

	protected function setTemplates()
	{
		$this->m_templates = array(
			(TEMPLATES_DIR . 'elements' . DS . 'ajax.ctp'),
			(TEMPLATES_DIR . 'elements' . DS . 'layout.ctp'),
		);

		return $this;
	}

	public function block($type = '')
	{
		$class = ($type ? $type . '_' : '') . 'Block_Component';
		$block = new $class('Block', $this->core);
		return $block->setBlockType($type)->setBlockState(true);
	}

	public function unit($type = '')
	{
		if ($this->m_errorPage)
			return $this;
		$class = ($type ? $type . '_' : '') . 'Unit_Component';
		$unit = new $class('Unit', $this->core);
		return $unit;
	}

	public function buildBlock($name)
	{
		if ($this->m_errorPage)
			return $this;

		if (method_exists($this, 'block_' . $name))
		{
			$block = &$this->{'block_' . $name}();
			$block->setBlockName($name);
			if ($block->getMainUnit() != null && method_exists($this, 'unit_' . $block->getMainUnit()))
			{
				if (isset($this->m_units[$block->getMainUnit()]))
					$unit = $this->m_units[$block->getMainUnit()];
				else
				{
					$unit = &$this->{'unit_' . $block->getMainUnit()}();
					$unit->findData();
				}
				$block->setMainUnitObject($unit);
				$this->m_units[$block->getMainUnit()] = $unit;
			}
		}

		return $this;
	}

	public function getUnit($unitName)
	{
		if (method_exists($this, 'unit_' . $unitName))
			return $this->{'unit_' . $unitName}();

		return $this->c('Unit');
	}

	public function buildBlocks($blocks)
	{
		if (is_array($blocks))
			foreach($blocks as $block)
				$this->buildBlock($block);

		return $this;
	}

	protected function addClientFiles()
	{
		if (is_array($this->m_css))
			foreach($this->m_css as $type => $css)
				$this->c('Document')->registerCss($css, $type);

		if (is_array($this->m_js))
			foreach($this->m_js as $type => $js)
				$this->c('Document')->registerJs($js, $type);

		return $this;
	}

	public function addPostAction($act)
	{
		if (!$act)
			return $this;

		$this->m_postBuild[] = $act;

		return $this;
	}

	private function performPostBuild()
	{
		if ($this->m_errorPage)
			return $this;

		if ($this->m_postBuild)
			foreach ($this->m_postBuild as &$post)
				if (is_array($post))
					if (method_exists($post[0], $post[1]))
						$post[0]->{$post[1]}();
				else
					if (function_exists($post))
						$post();

		return $this;
	}

	public function build($core)
	{
		$this->m_buildFailed = true;

		return $this;
	}

	protected function complete()
	{
		if ($this->m_skipBuild || $this->m_errorPage)
			return $this;

		return $this->renderRegions()->view();
	}

	private function renderRegions()
	{
		if ($this->m_errorPage)
			return $this;

		$regions = $this->c('Document')->getAllRegions();
		if (!$regions)
			return $this;

		foreach ($regions as &$region)
			$region->renderAllBlocks();

		return $this;
	}

	private function view()
	{
		if ($this->m_errorPage)
			return $this;

		if ($this->m_isAjax)
			$template = $this->m_templates[0];
		else
			$template = $this->m_templates[1];

		if (!file_exists($template))
			throw new PageCrash_Exception_Component('Unable to load page view!');

		$core_vars = $this->core->getCoreVars();

		if ($core_vars)
			extract($core_vars, EXTR_SKIP);

		include($template);

		return $this;
	}

	protected function ajaxPage()
	{
		$this->m_isAjax = true;

		return $this;
	}

	public function httpError($code = 404)
	{
		$this->m_skipBuild = true;
		$this->m_errorPage = true;

		$this->c('Appsession')->setData('errorCode', $code);

		$this->c('Default', 'Controller');

		return $this;
	}

	public function setErrorPage()
	{
		$this->m_errorPage = true;
		header('HTTP/1.0 404 Not Found');

		return $this;
	}

	public function isDefaultController()
	{
		return $this->m_isDefaultController;
	}

	public function delegateTo($controller)
	{
		$this->m_skipBuild = true;

		$this->c($controller, 'Controller');

		return $this;
	}
}