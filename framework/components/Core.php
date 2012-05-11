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

class Core_Component extends Component
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
		$this->m_component = 'Core';
		$this->m_time = microtime(true);
		$this->m_uniqueHash = uniqid(dechex(time()), true);
		$this->setVar('core', $this);

		if (!isset(self::$m_components['default']))
			self::$m_components['default'] = array();

		self::$m_components['default']['Core'] = $this;
	}

	public static function create()
	{
		$core = new Core_Component();

		$core->c('Events')
			->createEvent('onCoreStartup', array($core, 'onCoreStartup'))
			->createEvent('onCoreControllerSetup', array($core, 'onCoreControllerSetup'));

		return $core->initialize();
	}

	public function initialize()
	{
		$this->parseUrl();

		// Call router BEFORE controllers!
		//$this->c('Router');

		// Perform RunOnce
		//$this->c('RunOnce', 'Run');

		$this->m_activeController = null;

		$this->initController();

		return $this;
	}

	public function setVar($name, $value)
	{
		$this->m_coreVars[$name] = $value;

		return $this;
	}

	public function getVar($name)
	{
		return isset($this->m_coreVars[$name]) ? $this->m_coreVars[$name] : null;
	}

	public function getVars()
	{
		return $this->m_coreVars;
	}

	public function run()
	{
		return $this;
	}

	public function getRawUrl()
	{
		return $this->m_rawUrl;
	}

	public function getUrlAction($idx)
	{
		return isset($this->m_urlActions[$idx]) ? $this->m_urlActions[$idx] : '';
	}

	public function getActions()
	{
		return $this->m_urlActions;
	}

	public function getActionsCount()
	{
		return $this->m_urlActionsCount;
	}

	private function parseUrl()
	{
		$url = $this->c('Config')->getValue('core.url_string');

		if (!$url)
			throw new CoreCrash_Exception_Component('unable to find URL string!');

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

	public function isControllerExists($name)
	{
		$name = strtolower($name);

		$d = explode('_', $name);

		$size = sizeof($d);

		$t = '';

		for ($i = $size-1; $i >= 0; --$i)
		{
			if ($i == 0)
				$t .= DS . mb_convert_case($d[$i], MB_CASE_TITLE, 'UTF-8');
			else
				$t .= DS . mb_convert_case($d[$i], MB_CASE_LOWER, 'UTF-8');
		}

		$path = 'components' . DS . 'controller' . $t . '.' . PHP_EXT;

		foreach (array(APP_DIR, FW_DIR) as $type)
		{
			if (file_exists($type . $path))
				return true;
		}

		return false;
	}

	/**
	 * Performs controller initialization
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

			return $this->c('Home', 'Controller');
		}

		$tmp_name = '';
		$actions_count = $this->getActionsCount();

		for ($i = $actions_count - 1; $i >= 0; -- $i)
			$tmp_name .= $this->getUrlAction($i) . '_';

		$tmp_name = ucfirst(substr($tmp_name, 0, strlen($tmp_name)-1));

		if (!$this->isControllerExists($tmp_name))
		{
			$tmp_name = 'Home_' . $tmp_name;

			if (!$this->isControllerExists($tmp_name, true))
			{
				$found = false;
				$new_name = $tmp_name;
				$name_pieces = explode('_', substr($new_name, 5));
				$psize = sizeof($name_pieces);
				$cname = '';

				for ($i = 0; $i < $psize; ++$i)
				{
					if (!$found)
					{
						$cname = implode('_', $name_pieces);

						if ($this->isControllerExists($cname, true))
						{
							$found = true;
							$new_name = $cname;
						}

						array_unshift($name_pieces, 'Home');
						$cname = implode('_', $name_pieces);

						if ($this->isControllerExists($cname, true))
						{
							$found = true;
							$new_name = $cname;
						}

						array_shift($name_pieces);
						array_shift($name_pieces);
					}
				}

				if ($found)
				{
					$this->c('Events')->triggerEvent('onCoreControllerSetup', array('controller_name' => $new_name, 'default' => false), $this);

					return $this->c($new_name, 'Controller');
				}

				$this->c('Events')->triggerEvent('onCoreControllerSetup', array('controller_name' => 'Default', 'default' => true), $this);

				return $this->c('Default', 'Controller');
			}
			else
			{
				$this->c('Events')->triggerEvent('onCoreControllerSetup', array('controller_name' => $tmp_name, 'default' => false), $this);

				return $this->c($tmp_name, 'Controller');
			}
		}
		else
		{
			$this->c('Events')->triggerEvent('onCoreControllerSetup', array('controller_name' => $tmp_name, 'default' => false), $this);

			return $this->c($tmp_name, 'Controller');
		}
	}

	public function setActiveController($c)
	{
		$this->m_activeController = $c;

		return $this;
	}

	public function getActiveController()
	{
		return $this->m_activeController ? $this->m_activeController : $this->c('Default', 'Controller');
	}

	public function setHeader($header, $content = '')
	{
		if ($content)
			header($header . ': ' . $content);
		else
			header($header);

		return $this;
	}

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

	public function getPath($url = '')
	{
		if (!isset($this->m_paths[1]) || !$this->m_paths[1])
			$this->m_paths[1] = $this->c('Config')->getValue('app.cdn_path');

		return $this->m_paths[1] . (substr($url, 0, 1) == '/' ? $url : '/' . $url);
	}

	public function redirectTo($url)
	{
		$this->setHeader('Location', $url);

		exit;
	}

	public function onCoreControllerSetup($event) {}
};