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

class Db extends Component
{
	private $m_availableDatabases = array();

	/**
	 * __call overwrite required for fast access to any DB type
	 * e.g. instead of writing c('Db')->getDb('site') you can just write c('Db')->site()
	 * and get access to Database_Component for "site" DB type
	 * @param string $method
	 * @param array $args
	 * @return mixed
	 **/
	public function __call($method, $args)
	{
		if (method_exists($this, $method))
			return call_user_func_array(array($this, $method), $args);

		$db_type = strtolower($method);

		return $this->getDb($db_type);
	}

	/**
	 * Parses DB configs and creates instances for each database type.
	 * All connections created here are delayed (connection will be established only at first DB access)
	 * @return Db_Component
	 **/
	public function initialize()
	{
		$databases = $this->c('Config')->getValue('app.databases');

		if (!$databases)
			return $this;

		foreach ($databases as $type => $db)
		{
			if (!$db)
				continue;

			if (!isset($this->m_availableDatabases[$type]))
				$this->m_availableDatabases[$type] = array();

			if (isset($db['connectionString']))
			{
				$db['connected'] = false;
				$db['object'] = null;
				$db['type'] = $type;

				$this->m_availableDatabases[$type] = array(
					'single' => true,
					'configs' => $db,
					'activeId' => 1
				);
			}
			elseif (!isset($db['connectionString']) && is_array($db))
			{
				$this->m_availableDatabases[$type] = array(
					'single' => false,
					'configs' => array(),
					'activeId' => -1,
					'count' => 0
				);

				foreach ($db as $id => $conf)
				{
					$conf['connected'] = false;
					$conf['object'] = null;

					$this->m_availableDatabases[$type]['configs'][$id] = $conf;
					$this->m_availableDatabases[$type]['configs'][$id]['type'] = $type;

					if ($this->m_availableDatabases[$type]['activeId'] == -1 && $id > 0)
						$this->m_availableDatabases[$type]['activeId'] = $id;

					$this->m_availableDatabases[$type]['count']++;
				}
			}
		}

		return $this;
	}

	/**
	 * Returns DB for specific type
	 * @param string $type
	 * @param bool $skipConnection = false
	 * @throws \Exceptions\DBCrash
	 * @return Database_Component
	 **/
	public function getDb($type, $skipConnection = false)
	{
		if (!isset($this->m_availableDatabases[$type]))
			throw new \Exceptions\DBCrash('unknown database type: ' . $type);

		$dbo = null;

		if ($this->m_availableDatabases[$type]['single'])
		{
			if ($this->m_availableDatabases[$type]['configs']['connected'])
				$dbo = $this->m_availableDatabases[$type]['configs']['object'];
			else
			{
				$dbo = $this->i('Database')->connect($this->m_availableDatabases[$type]['configs'], $skipConnection);

				$this->m_availableDatabases[$type]['configs']['connected'] = true;
				$this->m_availableDatabases[$type]['configs']['object'] = $dbo;
			}
		}
		else
		{
			$activeId = $this->m_availableDatabases[$type]['activeId'];

			if (!isset($this->m_availableDatabases[$type]['configs'][$activeId]))
				return null;

			if ($this->m_availableDatabases[$type]['configs'][$activeId]['connected'])
				$dbo = $this->m_availableDatabases[$type]['configs'][$activeId]['object'];
			else
			{
				$dbo = $this->i('Database')->connect($this->m_availableDatabases[$type][$activeId]['configs'], $skipConnection);

				$this->m_availableDatabases[$type]['configs'][$activeId]['connected'] = true;
				$this->m_availableDatabases[$type]['configs'][$activeId]['object'] = $dbo;
			}
		}

		return $dbo;
	}

	/**
	 * Checks if database for specific type available (instance exists, connection availability skipped)
	 * @param string $type
	 * @return bool
	 **/
	public function isDatabaseAvailable($type)
	{
		return isset($this->m_availableDatabases[$type]);
	}

	/**
	 * Switches to specific database ID for $type (if databases count for $type type > 1)
	 * @param string $type
	 * @param int $id
	 * @return Db_Component
	 **/
	public function switchTo($type, $id)
	{
		if (!isset($this->m_availableDatabases[$type]))
			throw new \Exceptions\DBCrash('unknown database type: ' . $type);

		$this->m_availableDatabases[$type]['activeId'] = $id;
			
		return $this;
	}

	/**
	 * Returns statistics from all DBs for specific type
	 * @param string $type
	 * @return array
	 **/
	public function getStatistics($type)
	{
		if (!isset($this->m_availableDatabases[$type]))
			return array();

		if ($this->m_availableDatabases[$type]['single'])
		{
			if ($this->isDatabaseAvailable($type))
				return $this->getDb($type, true)->getStatistics();
		}
		else
		{
			$stat = array('queryCount' => 0, 'queryGenerationTime' => 0.0);

			for ($i = 1; $i <= $this->m_availableDatabases[$type]['count']; ++$i)
			{
				$this->switchTo($type, $i);

				if ($this->isDatabaseAvailable($type))
				{
					$tmp = $this->getDb($type, true)->getStatistics();

					$stat['queryCount'] += $tmp['queryCount'];
					$stat['queryGenerationTime'] += $tmp['queryGenerationTime'];
				}
			}
		}

		return array();
	}
};