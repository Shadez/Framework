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

	public function __call($method, $args)
	{
		return $this;
	}

	public function __construct($name, Core_Component &$core)
	{
		if (!$name)
			throw new CoreCrash_Exception_Component('component name was not provided');

		$this->m_core = $core;
		$this->m_component = $name;
		$this->m_time = microtime(true);
		$this->m_uniqueHash = uniqid(dechex(time()), true);
	}

	public function __destruct()
	{
		foreach ($this as $variable => &$value)
		{
			if (isset($this->{$variable}))
				unset($this->{$variable});
			elseif (isset(self::${$variable}))
				unset(self::${$variable});
		}

		unset($variable, $value);
	}

	public function initialize()
	{
		return $this;
	}

	public function setInitialized($value)
	{
		$this->m_initialized = $value;

		return $this;
	}

	public function isInitialized()
	{
		return $this->m_initialized;
	}

	public function getCore()
	{
		return $this->m_core;
	}

	public function core()
	{
		return $this->getCore();
	}

	public function c($name, $category = '')
	{
		if (!$name)
			throw new CoreCrash_Exception_Component('You must provide component name!');

		return $this->getComponent($name, $category);
	}

	public function i($name, $category = '')
	{
		if (!$name)
			throw new CoreCrash_Exception_Component('You must provide component name!');

		return $this->getComponent($name, $category, true);
	}

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

	private function addComponent($name, $category = 'default', &$c)
	{
		return $this;
	}

	public function shutdown()
	{
		return $this;
	}
};