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

class Db_Component extends Component
{
	private $m_databases = array();

	public function __call($method, $args)
	{
		if (method_exists($this, $method))
			return call_user_func_array(array($this, $method), $args);

		$db_type = strtolower($method);

		return $this->getDb($db_type);
	}

	protected function getDb($db_type)
	{
		$db = isset($this->m_databases[$db_type]) ? $this->m_databases[$db_type] : null;

		if (!$db)
			$this->core->terminate('Database ' . $db_type . ' was not found');

		if (!$db->isConnected())
			$db->delayedConnect();

		return $db;
	}

	public function isDatabaseAvailable($type)
	{
		return isset($this->m_databases[$type]);
	}

	public function initialize()
	{
		$databases = $this->c('Config')->getValue('database');

		if (!$databases)
			return $this;

		foreach ($databases as $type => $db)
		{
			if (!$db)
				continue;

			$this->m_databases[$type] = new Database_Component('Database', $this->core);
			$this->m_databases[$type]->connect($db); // Delayed connection, will be connection only on first request
		}

		return $this;
	}
}