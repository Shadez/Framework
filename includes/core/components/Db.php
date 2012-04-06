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
	private $m_availableDatabases = array();

	public function __call($method, $args)
	{
		$db_type = strtolower($method);

		return $this->getDb($db_type);
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

			if (!isset($this->m_availableDatabases[$type]))
				$this->m_availableDatabases[$type] = array();

			if (isset($db['host']))
			{
				$db['connected'] = false;
				$db['object'] = null;

				$this->m_availableDatabases[$type] = array(
					'single' => true,
					'configs' => $db,
					'activeId' => 1
				);
			}
			elseif (!isset($db['host']) && is_array($db))
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

					if ($this->m_availableDatabases[$type]['activeId'] == -1 && $id > 0)
						$this->m_availableDatabases[$type]['activeId'] = $id;

					$this->m_availableDatabases[$type]['count']++;
				}
			}
		}

		return $this;
	}

	protected function getDb($type, $skipConnection = false)
	{
		if (!isset($this->m_availableDatabases[$type]))
			throw new DBCrash_Exception_Component('unknown database type: ' . $type);

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

	public function isDatabaseAvailable($type)
	{
		return isset($this->m_availableDatabases[$type]);
	}

	public function switchTo($type, $id)
	{
		if (!isset($this->m_availableDatabases[$type]))
			throw new DBCrash_Exception_Component('unknown database type: ' . $type);

		$this->m_availableDatabases[$type]['activeId'] = $id;
			
		return $this;
	}

	public function getStatistics($type)
	{
		if (!isset($this->m_availableDatabases[$type]))
			return false;

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

		return false;
	}
}