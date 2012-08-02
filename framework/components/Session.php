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

class Session extends Component
{
	protected $m_sessionStorage = array();

	public function initialize()
	{
		$this->initSession();

		return $this;
	}

	/**
	 * Inits session
	 * @return Session_Component
	 **/
	protected function initSession()
	{
		$this->m_sessionStorage = $_SESSION;

		return $this;
	}

	/**
	 * Returns session value
	 * @param string $session
	 * @return mixed
	 **/
	public function getSession($session)
	{
		return $this->findSessionValue($session);
	}

	/**
	 * Sets session
	 * @param string $sess
	 * @param mixed $val
	 * @return Session_Component
	 **/
	public function setSession($sess, $val)
	{
		$this->m_sessionStorage[$sess] = $val;
		$_SESSION[$sess] = $val;

		return $this;
	}

	/**
	 * Finds session value
	 * @param string $session
	 * @throws \Exceptions\CoreCrash
	 * @return mixed
	 **/
	protected function findSessionValue($session)
	{
		if (!isset($this->m_sessionStorage[$session]) && !isset($_SESSION[$session]))
			return false;
		elseif (isset($this->m_sessionStorage[$session]))
			return $_SESSION[$session]; // $_SESSION is in priority
		elseif (isset($this->m_sessionStorage[$session]))
			return $this->m_sessionStorage[$session];

		throw new \Exceptions\CoreCrash('you\'ve just divided by zero');
	}
};