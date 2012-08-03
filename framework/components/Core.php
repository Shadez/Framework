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

class Core extends Component
{
	const URL_PATTERN = '/[^ \/_0-9A-Za-zА-Яа-я-]/';

	private $m_urlActions = array();
	private $m_urlActionsCount = 0;
	private $m_rawUrl = '';
	private $m_coreVars = array();
	private $m_activeController = null;
	private $m_paths = array();
	private $m_urlLocale = '';

	public function __construct()
	{
		$this->m_core = $this;
		$this->m_component = '\Core';
		$this->m_time = microtime(true);
		$this->m_uniqueHash = uniqid(dechex(time()), true);
		$this->setVar('core', $this);

		if (!isset(self::$m_components['default']))
			self::$m_components['default'] = array();

		self::$m_components['default']['\Core'] = $this;
	}

	public static function create()
	{
		$core = new Core();

		$core->c('Events')
			->createEvent('onCoreStartup', array($core, 'onCoreStartup'))
			->createEvent('onCoreControllerSetup', array($core, 'onCoreControllerSetup'));

		return $core->initialize();
	}

	public function initialize()
	{
		$this->parseUrl();

		// Perform RunOnce
		$this->c('\Run\RunOnce');

		$this->m_activeController = null;

		$this->initController();

		return $this;
	}

	/**
	 * Sets core variable
	 * @param string $name
	 * @param mixed $value
	 * @return Core_Component
	 **/
	public function setVar($name, $value)
	{
		$this->m_coreVars[$name] = $value;

		return $this;
	}

	/**
	 * Returns variable value
	 * @param string $name
	 * @return mixed
	 **/
	public function getVar($name)
	{
		return isset($this->m_coreVars[$name]) ? $this->m_coreVars[$name] : null;
	}

	/**
	 * Returns all core variables
	 * @return array
	 **/
	public function getVars()
	{
		return $this->m_coreVars;
	}

	public function run()
	{
		return $this;
	}

	/**
	 * Returns raw URL (as send by client)
	 * @return string
	 **/
	public function getRawUrl()
	{
		return $this->m_rawUrl;
	}

	/**
	 * Returns URL action by index
	 * @param int $idx
	 * @return string
	 **/
	public function getUrlAction($idx)
	{
		return isset($this->m_urlActions[$idx]) ? $this->m_urlActions[$idx] : '';
	}

	/**
	 * Returns all URL actions as URL string
	 * @return string
	 **/
	public function getActionsUrl()
	{
		return implode('/', $this->m_urlActions);
	}

	/**
	 * Returns all URL actions as array
	 * @return array
	 **/
	public function getActions()
	{
		return $this->m_urlActions;
	}

	/**
	 * Returns URL actions count
	 * @return int
	 **/
	public function getActionsCount()
	{
		return $this->m_urlActionsCount;
	}

	/**
	 * Parses URL string and explodes it into array
	 * @throws \Exceptions\CoreCrash
	 * @return Core_Component
	 **/
	private function parseUrl()
	{
		$url = $this->c('Config')->getValue('core.url_string');

		if (!$url)
			throw new \Exceptions\CoreCrash('unable to find URL string!');

		$this->m_urlActionsCount = 0;

		$pieces = isset($_GET[$url]) ? explode('/', $_GET[$url]) : array();

		if (!$pieces)
			return $this;

		$index = $this->c('Config')->getValue('i18n.localeIndex');
		$available = $this->c('Config')->getValue('i18n.available');
		$disabled = $this->c('Config')->getValue('i18n.disableLocaleIndex');

		foreach ($pieces as $id => $p)
		{
			if (!$p)
				continue;

			if (!$disabled && in_array($id, $index))
			{
				if (in_array($p, $available))
					$this->m_urlLocale = $p;
				else
					$this->m_urlActions[] = $p;
			}
			else
				$this->m_urlActions[] = $p;

			$this->m_rawUrl .= '/' . $p;
		}

		if (!$this->m_urlLocale)
		{
			if (!$disabled)
			{
				$cookie_locale = $this->c('Cookie')->read('locale');
				$redirect = $this->getUrl(($cookie_locale ? $cookie_locale : $this->c('Config')->getValue('i18n.default')) . $this->m_rawUrl);
				$this->redirectTo($redirect);
			}
		}
		else
			$this->c('Cookie')->write('locale', $this->m_urlLocale);

		$this->m_urlActionsCount = sizeof($this->m_urlActions);

		return $this;
	}

	private function getRewriteRuleController()
	{
		return null;
	}

	/**
	 * Checks if controller's file exists
	 * @param array $name
	 * @return bool
	 **/
	public function isControllerExists($name)
	{
		if (!$name)
			return false;

		foreach ($name as &$p)
			$p = strtolower($p);

		$name[sizeof($name) - 1] = ucfirst(strtolower($name[sizeof($name) - 1]));

		$path = 'components' . DS . 'controllers' . DS . implode(DS, $name) . '.' . PHP_EXT;

		foreach (array(APP_DIR, FW_DIR) as $type)
		{
			//dump($type.$path);
			if (file_exists($type . $path))
				return true;
		}

		return false;
	}

	/**
	 * Finds controller and performs it's initialization
	 * @return Core_Component
	 **/
	private function initController()
	{
		if (defined('SKIP_CONTROLLER'))
			return $this;

		$controller_name = $this->getRewriteRuleController();

		if (!$controller_name)
		{
			$controller_name = str_replace(' ', '', $this->getUrlAction(0));
			$controller_name = preg_replace('/[^ \/_A-Za-z-]/', '', $controller_name);
		}

		if (!$controller_name || $this->c('Config')->getValue('controller.home_only'))
		{
			$this->c('Events')->triggerEvent('onCoreControllerSetup', array('controller_name' => 'Home', 'default' => false), $this);

			return $this->c('Controllers\Home');
		}

		$tmp_name = array();
		$actions_count = $this->getActionsCount();

		for ($i = 0; $i < $actions_count; ++$i)
			$tmp_name[] = ucfirst(strtolower($this->getUrlAction($i)));

		$controller_name = '';

		while ($tmp_name)
		{
			if (!$this->isControllerExists($tmp_name))
				array_pop($tmp_name);
			else
			{
				$controller_name = implode('\\', $tmp_name);
				$tmp_name = null;
			}
		}

		if (!$controller_name)
			$controller_name = 'DefaultController';

		$this->c('Events')->triggerEvent('onCoreControllerSetup', array(
			'controller_name' => $controller_name,
			'default' => $controller_name == 'DefaultController'
		), $this);

		return $this->c('\Controllers\\' . $controller_name);
	}

	/**
	 * Sets active controller
	 * @param Controller_Component $c
	 * @return Core_Component
	 **/
	public function setActiveController($c)
	{
		$this->m_activeController = $c;

		return $this;
	}

	/**
	 * Returns active controller
	 * @return Controller_Component
	 **/
	public function getActiveController()
	{
		return $this->m_activeController ? $this->m_activeController : $this->c('\Controllers\DefaultController');
	}

	/**
	 * Sets header to output
	 * @param string $header
	 * @param strign $content = ''
	 * @return Core_Component
	 **/
	public function setHeader($header, $content = '')
	{
		if ($content)
			header($header . ': ' . $content);
		else
			header($header);

		return $this;
	}

	/**
	 * Append $url to full-path URL
	 * e.g. getUrl('account/') will transform into "/path-to-app/en/account/"
	 * @param string $url = ''
	 * @param bool $skipUrlLocale = false
	 * @return string
	 **/
	public function getUrl($url = '', $skipUrlLocale = false)
	{
		if (!isset($this->m_paths[0]) || !$this->m_paths[0])
		{
			$this->m_paths[0] = $this->c('Config')->getValue('app.path');

			if (!$this->c('Config')->getValue('i18n.disableLocaleIndex') && $this->m_urlLocale)
				$this->m_paths[0] = $this->m_paths[0] . (!$skipUrlLocale ? '/' . $this->m_urlLocale : '');
		}

		return $this->m_paths[0] . (substr($url, 0, 1) == '/' ? $url : '/' . $url);
	}

	/**
	 * Appends $url to CDN URL
	 * e.g. getPath('css/style.css') will transform into "http://cdn.example.org/css/style.css"
	 * @param string $url = ''
	 * @return string
	 **/
	public function getPath($url = '')
	{
		if (!isset($this->m_paths[1]) || !$this->m_paths[1])
			$this->m_paths[1] = $this->c('Config')->getValue('app.cdn_path');

		return $this->m_paths[1] . (substr($url, 0, 1) == '/' ? $url : '/' . $url);
	}

	/**
	 * Performs redirect to specific $url page
	 * Note that when header will be sent, current script will be terminated!
	 * @param string $url
	 * @return void
	 **/
	public function redirectTo($url)
	{
		$this->setHeader('Location', $url);

		exit;
	}

	/**
	 * Handler for onCoreControllerSetup event
	 * @param array $event
	 * @return void
	 **/
	public function onCoreControllerSetup($event) {}
};