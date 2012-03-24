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
	private $m_activeDatabases = array();
	private $m_databasesCount = array();

	public function __call($method, $args)
	{
		if (method_exists($this, $method))
			return call_user_func_array(array($this, $method), $args);

		$db_type = strtolower($method);

		return $this->getDb($db_type);
	}

	protected function getDb($db_type)
	{
		$db_h = isset($this->m_databases[$db_type]) ? $this->m_databases[$db_type] : null;

		$db = null;

		if (is_array($db_h))
		{
			if (isset($this->m_activeDatabases[$db_type]))
				$db = isset($this->m_databases[$db_type][$this->m_activeDatabases[$db_type]]) ? $this->m_databases[$db_type][$this->m_activeDatabases[$db_type]] : null;
			else
				$db = isset($this->m_databases[$db_type][1]) ? isset($this->m_databases[$db_type][1]) : null;
		}
		else
			$db = $db_h;

		if (!$db)
			$this->core->terminate('Database ' . $db_type . ' was not found');

		if (!$db->isConnected())
			$db->delayedConnect();

		unset($db_h);

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

			$this->m_databasesCount[$type] = array();

			if (isset($db['host']))
			{
				$this->m_databases[$type] = $this->i('Database')->connect($db); // Delayed connection, will be connected only on first request

				$this->m_databasesCount[$type] = array(
					'single' => true,
					'count' => 1
				);
			}
			elseif (!isset($db['host']) && is_array($db))
			{
				$id = 0;

				$this->m_databasesCount[$type] = array(
					'single' => false,
					'count' => 0
				);

				foreach ($db as $id => $data)
				{
					if (!isset($this->m_databases[$type]))
						$this->m_databases[$type] = array();

					$this->m_databases[$type][$id] = $this->i('Database')->connect($data); // Delayed connection, will be connected only on first request

					$this->m_databasesCount[$type]['count']++;
				}

				$this->switchTo($type, $id);
			}
		}

		return $this;
	}

	public function switchTo($type, $id)
	{
		$this->m_activeDatabases[$type] = $id;

		return $this;
	}

	public function getStatistics($type)
	{
		if (!isset($this->m_databasesCount[$type]))
			return false;

		if ($this->m_databasesCount[$type]['single'])
			return $this->getDb($type)->getStatistics();
		else
		{
			$stat = array('queryCount' => 0, 'queryGenerationTime' => 0.0);

			for ($i = 1; $i <= $this->m_databasesCount[$type]['count']; ++$i)
			{
				$tmp = $this->switchTo($type, $i)->getDb($type)->getStatistics();

				$stat['queryCount'] += $tmp['queryCount'];
				$stat['queryGenerationTime'] += $tmp['queryGenerationTime'];
			}
		}

		return $stat;
	}
}