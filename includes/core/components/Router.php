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

class Router_Component extends Component
{
	protected $m_router = array();

	public function initialize()
	{
		$this->initRouter();

		return $this;
	}

	/**
	 * Initializes router and redirects if all required conditions are met
	 * @access protected
	 * @return Router_Component
	 */
	protected function initRouter()
	{
		$router_file = SITE_DIR . 'Routes.php';

		if (!file_exists($router_file))
			return $this;

		require_once($router_file);
		$this->m_router = $Router;
		$url = $this->core->getAppUrl();

		if (!$url)
			$url = '/';

		if (isset($this->m_router[$url]))
		{
			$access = $this->m_router[$url]['access'];
			$destination = $this->m_router[$url]['destination'];
			$redirectType = $this->m_router[$url]['redirectType'];

			$redirectAllowed = false;

			if ($access == 'everyone')
				$redirectAllowed = true;
			elseif ($access == 'admins' && $this->c('Session')->getSession('isAdmin'))
				$redirectAllowed = true;
			elseif ($access == 'non-admins' && !$this->c('Session')->getSession('isAdmin'))
				$redirectAllowed = true;
			elseif ($access == 'guests' && !$this->c('Session')->getSession('isLoggedIn'))
				$redirectAllowed = true;
			elseif ($access == 'users' && $this->c('Session')->getSession('isLoggedIn'))
				$redirectAllowed = true;
			elseif ($access == 'only-users' && ($this->c('Session')->getSession('isLoggedIn') && !$this->c('Session')->getSession('isAdmin')))
				$redirectAllowed = true;

			if ($redirectAllowed)
			{
				if ($redirectType == 'app')
					$this->core->redirectApp((substr($destination, 0, 1) == '/' ? $destination : '/' . $destination));
				else
					$this->core->redirectUrl($destination);
			}
		}

		return $this;
	}
}