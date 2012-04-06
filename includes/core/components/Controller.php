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

class Controller_Component extends Component
{
	private $m_postBuild = array();
	private $m_blocks = array();
	protected $m_isDefaultController = false;
	protected $m_isAjax = false;
	protected $m_skipBuild = false;
	protected $m_dummy_page = false;
	protected $m_buildFailed = false;
	protected $m_menuIndex = null;
	protected $m_errorPage = false;
	protected $m_pageTitle = '';
	protected $m_menuTitle = '';
	protected $m_clientFilesController = '';
	protected $m_installerController = false;

	protected $m_templates = array();

	protected $m_css = array();
	protected $m_js = array();

	public function initialize()
	{
		$this->c('Events')
			->createEvent('onControllerStartup', array($this, 'onControllerStartup'))
			->createEvent('onControllerClientFilesSetup', array($this, 'onControllerClientFilesSetup'))
			->createEvent('onControllerClientFilesComplete', array($this, 'onControllerClientFilesComplete'))
			->createEvent('onControllerBuildStartup', array($this, 'onControllerBuildStartup'))
			->createEvent('onControllerBuildComplete', array($this, 'onControllerBuildComplete'));

		$this->core->setVar('l', $this->c('Locale'));
		$this->core->setVar('controller', $this);

		// If user's browser is Internet Explorer and admin won't allow
		// IE on this site, show warning message.
		if (IE_BROWSER && $this->c('Config')->getValue('site.disable_ie'))
		{
			require_once(TEMPLATES_DIR . 'elements' . DS . 'badbrowser.ctp');
			return $this;
		}

		// Perform controller actions

		$this->c('Events')->triggerEvent('onControllerStartup', array('controller' => $this), $this);

		$action = $this->core->getUrlAction(0);

		if (!$action)
			$action = 'home';

		$this->c('Layout')->loadClientFiles($this->m_css, $this->m_js, $action);

		$this->setTemplates() 			// Sets template directories (*)
			->preparePage() 			// Sets headers for ajax page (*)
			->beforeActions() 			// beforeClientFilesRegister event (*)
			->addClientFiles();			// Send controller files to Document_Component

		$this->c('Events')->triggerEvent('onControllerClientFilesComplete', array('controller' => $this), $this);

		$this->beforeBuild(); 			// beforeBuildStarted event (*)

		$this->c('Events')->triggerEvent('onControllerBuildStartup', array('controller' => $this), $this);

		$action = $this->core->getUrlAction(1);

		$this->build($this->core); // Build method [*]

		if ($action && method_exists($this, 'action' . $action))
			call_user_func(array($this, 'action' . $action), $this->core); // Build action [*]
		elseif (method_exists($this, 'actionIndex'))
			$this->actionIndex($this->core); // Build default action [*]

		$this->c('Events')->triggerEvent('onControllerBuildComplete', array('controller' => $this), $this);

		$this->pageTitle() 				// Sets page title (*)
			->performPostBuild() 		// Post Build Actions
			->complete(); 				// Finish

		// (*) -> method may be overriden
		// [*] -> method MUST be overriden

		return $this;
	}

	protected function beforeBuild()
	{
		return $this;
	}

	protected function preparePage()
	{
		if ($this->m_isAjax)
			$this->core->setHeader('Content-type', 'text/javascript', true);

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

	protected function setTemplates()
	{
		$this->m_templates = array(
			(TEMPLATES_DIR . 'elements' . DS . 'ajax.ctp'),
			(TEMPLATES_DIR . 'elements' . DS . 'layout.ctp'),
		);

		return $this;
	}

	public function block()
	{
		return $this->i('Block')->setBlockState(true);
	}

	public function buildBlock($name)
	{
		if ($this->m_errorPage)
			return $this;

		if (method_exists($this, 'block_' . $name))
		{
			$block = $this->{'block_' . $name}();
			$block->setBlockName($name);
		}

		return $this;
	}

	public function buildBlocks($blocks)
	{
		if (is_array($blocks))
		{
			foreach($blocks as $block)
				$this->buildBlock($block);
		}

		return $this;
	}

	private function addClientFiles()
	{
		$this->c('Document')->setCSS($this->m_css)
			->setJS($this->m_js);

		return $this;
	}

	public function addPostAction($act)
	{
		if (!$act)
			return $this;

		$this->m_postBuild[] = $act;

		return $this;
	}

	public function getName()
	{
		return strtolower(str_replace('_Controller_Component', '', get_class($this)));
	}

	private function performPostBuild()
	{
		if ($this->m_errorPage)
			return $this;

		if ($this->m_postBuild)
		{
			foreach ($this->m_postBuild as &$post)
			{
				if (is_array($post))
				{
					if (method_exists($post[0], $post[1]))
						$post[0]->{$post[1]}();
				}
				else
				{
					if (function_exists($post))
						$post();
				}
			}
		}

		return $this;
	}

	public function build($core)
	{
		$this->m_buildFailed = true;

		return $this;
	}

	private function complete()
	{
		if ($this->m_skipBuild || $this->m_errorPage)
			return $this;

		if ($this->isDefaultController())
			$this->core->setHeader('HTTP/1.0 404 Not Found', '', true);

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

		require_once($template);

		// Delete used core variables (not from core storage!)
		if ($core_vars)
		{
			foreach ($core_vars as $varName => &$varValue)
				unset($$varName);
		}

		return $this;
	}

	protected function ajaxPage($define = false)
	{
		$this->m_isAjax = true;

		if ($define)
		{
			define('AJAX_PAGE', true);
			$this->core->setHeader('Content-type', 'text/javascript', true);
		}

		return $this;
	}

	public function httpError($code = 404)
	{
		$this->m_skipBuild = true;
		$this->m_errorPage = true;

		$this->c('Session')->setSession('errorCode', $code);

		$this->c('Default', 'Controller');

		return $this;
	}

	public function setErrorPage()
	{
		$this->m_errorPage = true;

		$this->core->setHeader('HTTP/1.0 404 Not Found', '');

		return $this;
	}

	public function isDefaultController()
	{
		return $this->m_isDefaultController;
	}

	public function delegateTo($c_name)
	{
		$this->m_skipBuild = true;

		$this->c($c_name, 'Controller');

		return $this;
	}

	protected function outputImage($image, $type)
	{
		switch ($type)
		{
			case 1:
				$this->core->setHeader('Content-type', 'image/jpeg', true);
				imagejpeg($image);
				exit;
			case 2:
				$this->core->setHeader('Content-type', 'image/png', true);
				imagepng($image);
				exit;
			case 3:
				$this->core->setHeader('Content-type', 'image/gif', true);
				imagegif($image);
				exit;
		}
	}

	/*
	 * Events
	 */

	public function onControllerStartup($event) {}
	public function onControllerClientFilesSetup($event) {}
	public function onControllerClientFilesComplete($event) {}
	public function onControllerBuildStartup($event) {}
	public function onControllerBuildComplete($event) {}
}