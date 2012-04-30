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

class Database_Component extends Component
{
	private $m_pdo = null;
	private $m_configs = array();
	private $m_resultData = array();
	private $m_queriesCount = 0;
	private $m_queriesTime = 0.0;
	private $m_connected = false;
	private $m_transaction = array();
	private $m_lastInsertId = 0;

	public function connect($configs, $delayed = true)
	{
		if ($configs)
			$this->m_configs = $configs;

		if ($delayed)
			return $this;

		if (!$this->m_configs)
			throw new DatabaseCrash_Exception_Component('connection info was not found');

		try
		{
			$this->m_pdo = new PDO($this->m_configs['connectionString'], $this->m_configs['user'], $this->m_configs['password']);
		}
		catch (Exception $e)
		{
			throw new DatabaseCrash_Exception_Component($e->getMessage());
		}

		$this->m_connected = true;

		$this->executeWithParams('SET NAMES :charset', array(':charset' => $this->m_configs['charset']));

		return $this;
	}

	private function execute($stmtSql, $params = array(), $fetch = false)
	{
		if (!$this->m_pdo)
			return $this;

		$query_start = microtime(true);
		$sql = '';

		$this->m_resultData = array();

		try
		{
			$stmt = $this->m_pdo->prepare($stmtSql);
			$stmt->execute($params);
			$sql = $stmt->queryString;

			if ($fetch)
				$this->m_resultData = $stmt->fetchAll(PDO::FETCH_ASSOC);
			elseif (strpos(strtolower($stmtSql), 'insert into') !== false)
				$this->m_lastInsertId = $this->m_pdo->lastInsertId();

			$stmt = null;
		}
		catch (Exception $e)
		{
			throw new DatabaseCrash_Exception_Component($e->getMessage());
		}

		$query_time = round(microtime(true) - $query_start, 4);

		$this->c('Log')->writeSql('[%s ms]: %s', $query_time, $sql);

		$this->m_queriesTime += $query_time;
		$this->m_queriesCount++;

		return $this;
	}

	public function isConnected()
	{
		return $this->m_connected;
	}

	public function getStatistics()
	{
		return array('queryCount' => $this->m_queriesCount, 'queryGenerationTime' => $this->m_queriesTime);
	}

	public function executeWithParams($stmtSql, $params = array())
	{
		return $this->execute($stmtSql, $params);
	}

	public function selectWithParams($stmtSql, $params = array())
	{
		return $this->execute($stmtSql, $params, true);
	}

	public function createTransaction()
	{
		if (!$this->m_pdo)
			return $this;

		$this->m_pdo->beginTransaction();

		$this->m_transaction = array(
			'state' => 'started',
			'errors' => false
		);

		return $this;
	}

	public function transact($sql)
	{
		if (!$this->m_pdo)
			return $this;

		if ($this->m_pdo->exec($sql) === false)
			$this->m_transaction['errors'] = true;

		return $this;
	}

	public function commitTransaction()
	{
		if (!$this->m_pdo)
			return $this;

		if (!$this->m_transaction['errors'])
			$this->m_pdo->commit();
		else
		{
			$this->m_pdo->rollback();
			$this->m_transaction['state'] = 'failed';
			$this->m_transaction['errors'] = false;
		}

		return $this;
	}

	public function getTranscationInfo($info)
	{
		return isset($this->m_transaction[$info]) ? $this->m_transaction[$info] : null;
	}

	public function setIndexKey($field, $multiply = false)
	{
		if (!$this->m_resultData)
			return $this;

		$data = array();

		$info = array();

		foreach ($this->m_resultData as $row)
		{
			if (isset($row[$field]))
			{
				if ($multiply)
				{
					if (isset($data[$row[$field]]))
					{
						if (isset($info[$row[$field]]))
						{
							if ($info[$row[$field]])
							{
								$data[$row[$field]] = array($data[$row[$field]]);
								$info[$row[$field]] = false;
							}
							$data[$row[$field]][] = $row;
						}
						else
						{
							$info[$row[$field]] = true;
							$data[$row[$field]] = $row;
						}
					}
					else
						$data[$row[$field]] = $row;
				}
				else
					$data[$row[$field]] = $row;
			}
		}

		$this->m_resultData = $data;

		unset($data);

		return $this;
	}

	public function getData()
	{
		return $this->m_resultData;
	}

	public function disconnect()
	{
		$this->m_pdo = null;

		return $this;
	}

	public function getInsertId()
	{
		return $this->m_lastInsertId;
	}
};