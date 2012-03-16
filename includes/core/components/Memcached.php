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
 * This component is under development
 **/
class Memcached_Component extends Component
{
	private $m_servers = array();
	private $m_cacheHandler = null;
	private $m_keyPrefix = '';

	public function initialize()
	{
		return $this->setServers();
	}

	private function setServers()
	{
		$this->m_keyPrefix = md5($_SERVER['SERVER_NAME']);

		return $this;
	}

	public function setValue($key, $value, $expires)
	{
		return $this;
	}

	public function getValue($key)
	{
		return false;
	}

	public function deleteValue($key)
	{
		return $this;
	}

	public function flushValues()
	{
		return $this;
	}

	public function isCachingAvailable()
	{
		return false;
	}

	public function getHandler()
	{
		return $this->m_cacheHandler;
	}

	public function generateKey($key)
	{
		return md5($this->m_keyPrefix . $key);
	}
}