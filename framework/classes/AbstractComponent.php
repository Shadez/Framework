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

abstract class Component
{
	protected static $m_components = array();
	protected $m_core = null;
	protected $m_component = null;
	protected $m_initialized = false;
	protected $m_uniqueHash = '';
	protected $m_time = 0;

	/**
	 * Component constructor
	 * @param string $name
	 * @param Core_Component $core
	 * @throws CoreCrash_Exception_Component
	 **/
	public function __construct($name, Core_Component $core)
	{
		if (!$name)
			throw new CoreCrash_Exception_Component('component name was not provided');

		$this->m_core = $core;
		$this->m_component = $name;
		$this->m_time = microtime(true);
		$this->m_uniqueHash = uniqid(dechex(time()), true);
	}

	/**
	 * Component destructor
	 **/
	public function __destruct()
	{
		foreach ($this as $variable => $value)
		{
			if (isset($this->{$variable}))
				unset($this->{$variable});
			elseif (isset(self::${$variable}))
				unset(self::${$variable});
		}

		unset($variable, $value);
	}

	/**
	 * Method called after component creation
	 * @return Component
	 **/
	public function initialize()
	{
		return $this;
	}

	/**
	 * Turns component's initialization state
	 * @param bool $value
	 * @return Component
	 **/
	public function setInitialized($value)
	{
		$this->m_initialized = $value;

		return $this;
	}

	/**
	 * Returns component's initialization state
	 * @return bool
	 **/
	public function isInitialized()
	{
		return $this->m_initialized;
	}

	/**
	 * Returns Core_Component instance (singleton)
	 * @return Core_Component
	 **/
	public function getCore()
	{
		return $this->m_core;
	}

	/**
	 * Deprecated method to get Core_Component instance
	 * Left for compatibility with old projects
	 * @return Core_Component
	 **/
	public function core()
	{
		return $this->getCore();
	}

	/**
	 * Creates new or returns previously created instance of provided component name
	 * This method should be used for singletons only!
	 * @param string $name
	 * @param string $category = ''
	 * @throws CoreCrash_Exception_Component
	 * @return Component
	 **/
	public function c($name, $category = '')
	{
		if (!$name)
			throw new CoreCrash_Exception_Component('You must provide component name!');

		return $this->getComponent($name, $category);
	}

	/**
	 * Creates and returns new instance of provided component name
	 * This method should not be used to get access to singleton component!
	 * @param string $name
	 * @param string $category = ''
	 * @throws CoreCrash_Exception_Component
	 * @return Component
	 **/
	public function i($name, $category = '')
	{
		if (!$name)
			throw new CoreCrash_Exception_Component('You must provide component name!');

		return $this->getComponent($name, $category, true);
	}

	/**
	 * Finds or creates instance of component
	 * @param string $name
	 * @param string $category = ''
	 * @param bool $createNew = false
	 * @throws CoreCrash_Exception_Component
	 * @return Component
	 **/
	private function getComponent($name, $category = '', $createNew = false)
	{
		$cmp_name = ucfirst(strtolower($name)) . ($category ? '_' . $category : '') . '_Component';

		if ($createNew)
		{
			// Check singletons
			$singletons = array(
				'Core', 'Db', 'Events'
			);

			if (in_array(ucfirst(strtolower($name)), $singletons))
				throw new CoreCrash_Exception_Component('there is can be only one instance of ' . $name . ' component');
		
			$cmp = new $cmp_name($name, $this->m_core);

			return $cmp->initialize()->setInitialized(true);
		}

		if (!isset(self::$m_components[$category]))
			self::$m_components[$category] = array();
		else
		{
			if (isset(self::$m_components[$category][$name]))
				return self::$m_components[$category][$name];
		}

		$cmp = new $cmp_name($name, $this->m_core);
		self::$m_components[$category][$name] = $cmp;

		return $cmp->initialize()->setInitialized(true);
	}

	/**
	 * Method called before component instance will be deleted
	 * Usable for correct component termination
	 * @return Component
	 **/
	public function shutdown()
	{
		return $this;
	}

	/**
	 * Merges contents of two arrays into first one
	 * @param array &$to
	 * @param array $from
	 * @return array
	 **/
	public function extend(&$to, $from)
	{
		if (!$from || !$to)
			return $this;

		foreach ($from as $k => $v)
			if (isset($to[$k]))
				$to[$k] = $v;

		return $this;
	}
};