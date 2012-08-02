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

class SiteApi extends Component
{
	public function initialize()
	{
		$this->c('Events')
			->createEvent('onApiSignatureCheck', array($this, 'onApiSignatureCheck'));

		return $this;
	}

	public function onApiSignatureCheck($event)
	{
		return isset($event['eventData']['sig']) ? array('sig' => $event['eventData']) : array();
	}

	public function isApiMethodImplemented($method)
	{
		return method_exists($this, 'apiMethod_' . $method);
	}

	public function runApiMethod($method, $data)
	{
		if (!$method || !isset($method['name']))
			return false;

		return call_user_func_array(array($this, 'apiMethod_' . $method['name']), array(
			'method' => $method,
			'data' => $data
		));
	}

	protected function apiMethod_sitegetusername($method, $data)
	{
		return array(
			'username' => 'Shadez'
		);
	}
};