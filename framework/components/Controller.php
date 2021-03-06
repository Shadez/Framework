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

abstract class Controller extends Component
{
	protected $m_ajaxPage = false;
	protected $m_buildFailed = false;
	protected $m_controllerName = '';
	protected $m_controllerVars = array();
	protected $m_isDefaultController = false;
	protected $m_errorPage = false;
	protected $m_skipBuild = false;
	protected $m_pageTitle = '';
	protected $m_webActionIndex = -1;
	protected $m_clientFilesControllerName = '';
	protected $m_controllerAction = '';
	protected $m_controllerGroup = '';
	protected $m_blocks = array();

	/**
	 * Initializes controller and performs correct action (depends on URL string)
	 * @return Controller_Component
	 **/
	public function initialize()
	{
		$this->c('Events')
			->createEvent('onControllerStartup', array($this, 'onControllerStartup'));

		$this->getCore()
			->setVar('l', $this->c('I18n'))
			->setVar('controller', $this);

		// If user's browser is Internet Explorer and admin won't allow
		// IE on this site, show warning message.
		if (IE_BROWSER && $this->c('Config')->getValue('app.disable_ie'))
		{
			$this->c('View')->displayTemplate('badbrowser', 'default.pages');

			return $this;
		}

		$this->c('Events')->triggerEvent('onControllerStartup', array('controller' => $this), $this);

		// Try to find controller in url
		$action = '';

		if ($this->m_webActionIndex >= 0)
			$action = $this->getCore()->getUrlAction($this->m_webActionIndex);
		else
		{
			$c_name = $this->getName();
			$size = sizeof(explode('\\', $c_name));

			$action = $this->getCore()->getUrlAction($size);
		}

		if ($this->m_isDefaultController)
			$this->setErrorPage();

		if (!$action)
			$action = 'index';

		$this->m_controllerAction = $action;

		$this->getCore()->setActiveController($this);

		$this->run();

		if (method_exists($this, 'action' . $action))
			call_user_func(array($this, 'action' . $action));
		elseif($action != 'index' && method_exists($this, 'actionIndex'))
			$this->actionIndex();

		if ($this->m_pageTitle)
			$this->c('Layout')->setPageTitle($this->m_pageTitle);

		$this->end();

		$this->finish();

		return $this;
	}

	/**
	 * Returns controller name
	 * @return string
	 **/
	public function getName()
	{
		if (!$this->m_controllerName)
			$this->m_controllerName = strtolower(str_replace('controllers\\', '', strtolower(get_class($this))));

		return $this->m_controllerName;
	}

	/**
	 * Sets controller var value
	 * @param string $name
	 * @param mixed $value
	 * @return Controller_Component
	 **/
	public function setVar($name, $value)
	{
		$this->m_controllerVars[$name] = $value;

		return $this;
	}

	/**
	 * Returns controller's var value
	 * @param string $name
	 * @return mixed
	 **/
	public function getVar($name)
	{
		return isset($this->m_controllerVars[$name]) ? $this->m_controllerVars[$name] : null;
	}

	/**
	 * Returns all controller vars
	 * @return array
	 **/
	public function getVars()
	{
		return $this->m_controllerVars;
	}

	/**
	 * Returns controller name used for client files
	 * @return string
	 **/
	public function getClientFilesControllerName()
	{
		return $this->m_clientFilesControllerName ? $this->m_clientFilesControllerName : $this->getName();
	}

	/**
	 * Returns active controller action
	 * @return string
	 **/
	public function getControllerAction()
	{
		return $this->m_controllerAction;
	}

	/**
	 * Returns controller group name
	 * @return string
	 **/
	public function getControllerGroup()
	{
		return $this->m_controllerGroup;
	}

	/**
	 * Sets page's state to error
	 * @return Controller_Component
	 **/
	public function setErrorPage()
	{
		$this->m_errorPage = true;

		$this->getCore()->setHeader('HTTP/1.0 404 Not Found');

		return $this;
	}

	/**
	 * Sets page's state to AJAX
	 * @return Controller_Component
	 **/
	public function setAjaxPage()
	{
		$this->m_ajaxPage = true;

		$this->setContentType('text/javascript');

		return $this;
	}

	/**
	 * Returns page's AJAX state
	 * @return bool
	 **/
	public function isAjaxPage()
	{
		return $this->m_ajaxPage;
	}

	/**
	 * Sets specific content type for page ("Content-type" header)
	 * @param string $mime
	 * @return Controller_Component
	 **/
	public function setContentType($mime)
	{
		$this->getCore()->setHeader('Content-type', $mime);

		return $this;
	}

	/**
	 * Method called after controller initialization
	 * @return Controller_Component
	 **/
	protected function run()
	{
		return $this;
	}

	/**
	 * Method called after controller work but before View initialization
	 * @return Controller_Component
	 **/
	protected function end()
	{
		return $this;
	}

	/**
	 * Default controller action
	 * @return Controller_Component
	 **/
	protected function actionIndex()
	{
		return $this;
	}

	/**
	 * Last method executed by controller, initializes View
	 * @return COntroller_Component
	 **/
	protected function finish()
	{
		$this->c('View')->buildBlocks($this->m_blocks)->buildPage();

		return $this;
	}

	/*
	 * Events
	 */

	/**
	 * Handler for onControllerStartup event
	 * @param array $event
	 * @return void
	 **/
	public function onControllerStartup($event) {}
};