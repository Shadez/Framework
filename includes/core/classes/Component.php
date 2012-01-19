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

/**
 * Abstract class that provides basic methods for Components
 * @copyright Copyright (C) 2009-2011 Shadez <https://github.com/Shadez>
 * @category  Core
 * @abstract
 **/
abstract class Component
{
	/**
	 * All available components
	 * @access protected
	 * @var    array
	 * @static
	 **/
	protected static $m_components = array();

	/**
	 * Core component instance
	 * @access public
	 * @var    Core_Component
	 **/
	public $core = null;

	/**
	 * Current component name
	 * @access protected
	 * @var    string
	 **/
	protected $m_component = null;

	/**
	 * Is component loaded? Yes (true) / No (false)
	 * @access protected
	 * @var    bool
	 **/
	protected $m_initialized = false;

	/**
	 * Unique component hash
	 * @access protected
	 * @var    string
	 **/
	protected $m_uniqueHash = '';

	protected $m_locale = '';
	protected $m_localeID = 0;
	protected $m_coreUrl = '';

	/**
	 * Components rewrite info
	 * @access private
	 * @var	   array
	 **/
	private $m_componentsRewrite = array();

	/**
	 * Class constructor
	 * @access public
	 * @param  string $name
	 * @param  Component $core
	 * @return void
	 **/
	public function __construct($name, Component $core)
	{
		if (!$name)
			throw new CoreCrash_Exception_Component('Component name was not provided!');

		$this->core = $core;
		$this->m_component = $name;

		$this->m_uniqueHash = uniqid(dechex(time()), true);
	}

	public function localePath()
	{
		return $this->m_locale . '/';
	}

	/**
	 * Class destructor
	 * @access public
	 * @return void
	 **/
	public function __destruct()
	{
		foreach ($this as $variable => &$value)
		{
			if (isset($this->{$variable}))
				unset($this->{$variable});
			elseif (isset(self::${$variable}))
				unset(self::${$variable});
		}
	}
	
	/**
	 * Initializes Component's object
	 * @access public
	 * @return Component
	 **/
	public function initialize()
	{
		return $this;
	}

	/**
	 * Initialization checker
	 * @access public
	 * @return bool
	 **/
	public function isInitialized()
	{
		return $this->m_initialized;
	}
	
	/**
	 * Sets initialization state
	 * @access public
	 * @param  bool $value
	 * @return Component
	 **/
	public function setInitialized($value)
	{
		$this->m_initialized = $value;

		return $this;
	}
	
	/**
	 * Returns or tries to create component
	 * @access public
	 * @param  string $name
	 * @param  string $type = ''
	 * @return Component
	 **/
	public function c($name, $type = '')
	{
		if (!$name)
			throw new CoreCrash_Exception_Component('You must provide component name!');

		return $this->getComponent($name, $type);
	}

	/**
	 * Creates and returns component instance.
	 * Please, note that component instance will be created at every Component::i() call!
	 * @access public
	 * @param  string $name
	 * @param  string $type = ''
	 * @return Component
	 **/
	public function i($name, $type = '')
	{
		if (!$name)
			throw new CoreCrash_Exception_Component('You must provide component name!');

		$singletons = array(
			'Core', 'Db'
		); // Component names that must have only one instance during app work

		if (in_array($name, $singletons))
			throw new CoreCrash_Exception_Component('You are not allowed to create more that 1 instance of ' . $name . ' Component (use Component::c() method instead)!');

		$c_name = ucfirst(strtolower($name)) . ($type ? '_' . $type : '') . '_Component';

		$c_name = str_replace('-', '', $c_name);

		//$this->c('Log')->writeComponent('%s : creating component %s', __METHOD__, $c_name);

		$rewrite = $this->getComponentRewrite($name, $type);

		if ($rewrite)
		{
			// Load overriden class here
			$fname = 'components' . DS . ($type ? strtolower($type) . 's' : '') . DS . ucfirst(strtolower($rewrite)) . '.php';

			if (file_exists(SITE_DIR . $fname))
				include(SITE_DIR . $fname);
			elseif (file_exists(CORE_DIR . $fname))
				include(CORE_DIR . $fname);
		}

		$component = new $c_name($c_name, $this->core);

		// Since we are about to use new at every i() call, we must skip addComponent() method.

		return $component->initialize()->setInitialized(true); // Init component and return it.
	}

	private function getComponentRewrite($c_name, $c_type = 'Default')
	{
		if (!$this->m_componentsRewrite)
		{
			include(SITE_DIR . 'ComponentsRewrite.php');

			if (!isset($Components))
				return false;

			$this->m_componentsRewrite = $Components;

			unset($Components);
		}

		if (isset($this->m_componentsRewrite[$c_type]))
		{
			if (isset($this->m_componentsRewrite[$c_type][$c_name]))
			{
				$allowable_types = array('config', 'get', 'post', 'session', 'cookie');
				foreach ($allowable_types as $type)
				{
					if (!isset($this->m_componentsRewrite[$c_type][$c_name]['conditions'][$type]))
						continue;

					$cond_keys = array_keys($this->m_componentsRewrite[$c_type][$c_name]['conditions'][$type]);

					if (!isset($cond_keys[0]) || !$cond_keys[0])
						continue;

					$var_key = $cond_keys[0];
					$var_val = null;

					switch ($type)
					{
						case 'config':
							$var_val = $this->c('Config')->getValue($var_key);
							break;
						case 'get':
							$var_val = isset($_GET[$var_key]) ? $_GET[$var_key] : null;
							break;
						case 'post':
							$var_val = isset($_POST[$var_key]) ? $_POST[$var_key] : null;
							break;
						case 'session':
							$var_val = isset($_SESSION[$var_key]) ? $_SESSION[$var_key] : null;
							break;
						case 'cookie':
							$var_val = isset($_COOKIE[$var_key]) ? $_COOKIE[$var_key] : null;
							break;
						default:
							return null;
					}

					foreach ($this->m_componentsRewrite[$c_type][$c_name]['conditions'][$type][$var_key] as $val => $cname)
						if ($val == $var_val)
							return $cname;
				}
			}
		}

		return null;
	}
	
	/**
	 * Tries to find existed instance of $name component or creates new object
	 * @access private
	 * @param  string $name
	 * @param  string $type = ''
	 * @return Component
	 **/
	private function getComponent($name, $type = '')
	{
		$c_name = ucfirst(strtolower($name)) . ($type ? '_' . $type : '') . '_Component';

		if ($type == '')
			$c_type = 'default';
		else
			$c_type = strtolower($type);

		if (!isset(self::$m_components[$c_type]))
			self::$m_components[$c_type] = array();

		if (isset(self::$m_components[$c_type][$name]))
			return self::$m_components[$c_type][$name];

		//TODO: Try to check class file existence before create instance of class.
		//If this will be implemented, we'll can safely handle controller errors (404).

		$c_name = str_replace('-', '', $c_name);

		//$this->c('Log')->writeComponent('%s : creating component %s', __METHOD__, $c_name);

		$rewrite = $this->getComponentRewrite($name, $type);

		if ($rewrite)
		{
			// Load overriden class here
			$fname = 'components' . ($type ? strtolower($type) . 's' . DS : '') . DS . ucfirst(strtolower($rewrite)) . '.php';

			if (file_exists(SITE_DIR . $fname))
				include(SITE_DIR . $fname);
			elseif (file_exists(CORE_DIR . $fname))
				include(CORE_DIR . $fname);
		}

		$component = new $c_name($c_name, $this->core); // 

		$this->addComponent($name, $c_type, $component);

		return $component->initialize()->setInitialized(true);
	}

	/**
	 * Adds component into components list
	 * @access private
	 * @param  string $name
	 * @param  Component $c
	 * @return Component
	 **/
	private function addComponent($name, $type, $c)
	{
		if (!isset(self::$m_components[$type]))
			self::$m_components[$type] = array();

		self::$m_components[$type][$name] = $c;

		return $this;
	}

	public function getPath($file = '')
	{
		$path = $this->c('Config')->getValue('site.path');
		if ($file)
			$path .= $file;

		return $path;
	}

	public function issetRegion($name)
	{
		return $this->c('Document')->regionExists($name);
	}

	public function region($name)
	{
		return $this->c('Page')->getContents($name);
	}

	public function shutdownComponent()
	{
		foreach ($this as &$field)
			unset($field);

		return $this;
	}

	public static function prepareShutdown()
	{
		foreach (self::$m_components as $type => &$components)
		{
			foreach ($components as $name => &$component)
			{
				if ($type == 'default' && $name == 'Core')
					continue;
				else
				{
					$component->shutdownComponent();
					unset($component, self::$m_components[$type][$name]);
				}
			}
			if ($type != 'default')
				unset(self::$m_components[$type]);
		}
	}

	public function getUrl($url = '')
	{
		if (!$this->m_locale)
			$this->m_locale = $this->c('Locale')->GetLocale();

		if ($this->m_coreUrl != null)
			return $this->m_coreUrl . '/' . $url;

		if (!defined('CLIENT_FILES_PATH'))
			define('CLIENT_FILES_PATH', $this->c('Config')->getValue('site.path'));

		$this->m_coreUrl = CLIENT_FILES_PATH;

		return $this->m_coreUrl . '/' . $url;
	}

	public function getCFP($url = '')
	{
		if (!defined('CLIENT_FILES_PATH'))
			define('CLIENT_FILES_PATH', $this->c('Config')->getValue('site.path'));

		return CLIENT_FILES_PATH . '/' . $url;
	}

	public function getPage($asOffset = false)
	{
		if (!isset($_GET['page']))
			return $asOffset ? 0 : 1;

		return $asOffset ? (intval($_GET['page']) - 1) : intval($_GET['page']);
	}
}