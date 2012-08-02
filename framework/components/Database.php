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

class Database extends Component
{
	private $m_pdo = null;
	private $m_configs = array();
	private $m_resultData = array();
	private $m_queriesCount = 0;
	private $m_queriesTime = 0.0;
	private $m_connected = false;
	private $m_transaction = array();
	private $m_lastInsertId = 0;

	/**
	 * Performs immediate connection to DB or saves configs to perform delayed connection
	 * @param array $configs
	 * @param bool $delayed = true
	 * @throws \Exceptions\DatabaseCrash
	 * @return Database_Component
	 **/
	public function connect($configs, $delayed = true)
	{
		if ($configs)
			$this->m_configs = $configs;

		if ($delayed)
			return $this;

		if (!$this->m_configs)
			throw new \Exceptions\DatabaseCrash('connection info was not found');

		try
		{
			$this->m_pdo = new PDO($this->m_configs['connectionString'], $this->m_configs['user'], $this->m_configs['password']);
		}
		catch (Exception $e)
		{
			throw new \Exceptions\DatabaseCrash($e->getMessage());
		}

		$this->m_connected = true;

		$this->executeWithParams('SET NAMES :charset', array(':charset' => $this->m_configs['charset']));

		return $this;
	}

	/**
	 * Converts array to string
	 * @param array &$input
	 * @return Database_Component
	 **/
	private function convertArray(&$input)
	{
		if (!$input)
			return $this;

		foreach ($input as &$v)
			if (is_string($v))
				$v = '\'' . $v . '\'';

		$input = implode(', ', $input);

		return $this;
	}

	/**
	 * Executes SQL query to database
	 * @param string $stmtSql
	 * @param array $params = array()
	 * @param bool $fetch = false
	 * @throws \Exceptions\DatabaseCrash
	 * @return Database_Component
	 **/
	private function execute($stmtSql, $params = array(), $fetch = false)
	{
		if (!$this->m_pdo)
			return $this;

		$query_start = microtime(true);
		$sql = '';

		$this->m_resultData = array();
		$logSql = '';

		try
		{
			$stmt = $this->m_pdo->prepare($stmtSql);

			foreach ($params as &$p)
				if (is_array($p))
					$this->convertArray($p);

			$stmt->execute($params);
			$sql = $stmt->queryString;
			$logSql = $sql;

			if ($params)
				foreach ($params as $t => $p)
					$logSql = str_replace($t, '\'' . $p . '\'', $logSql);

			if ($fetch)
				$this->m_resultData = $stmt->fetchAll(PDO::FETCH_ASSOC);
			elseif (strpos(strtolower($stmtSql), 'insert into') !== false)
				$this->m_lastInsertId = $this->m_pdo->lastInsertId();

			$stmt = null;
		}
		catch (Exception $e)
		{
			throw new \Exceptions\DatabaseCrash($e->getMessage());
		}

		$query_time = round(microtime(true) - $query_start, 4);

		$this->c('Log')->writeSql('[' . $this->m_configs['type'] . ', %s ms]: %s', $query_time, $logSql);

		$this->m_queriesTime += $query_time;
		$this->m_queriesCount++;

		return $this;
	}

	/**
	 * Checks if connection to DB is established
	 * @return bool
	 **/
	public function isConnected()
	{
		return $this->m_connected;
	}

	/**
	 * Returns DB statistics: queries count and queries execution time
	 * @return array
	 **/
	public function getStatistics()
	{
		return array('queryCount' => $this->m_queriesCount, 'queryGenerationTime' => $this->m_queriesTime);
	}

	/**
	 * Executes SQL with bounded parameters
	 * @param string $stmtSql
	 * @param array $params = array()
	 * @return Database_Component
	 **/
	public function executeWithParams($stmtSql, $params = array())
	{
		return $this->execute($stmtSql, $params);
	}

	/**
	 * Executes SQL with bounded parameters and fetches results
	 * @param string $stmtSql
	 * @param array $params = array()
	 * @return Database_Component
	 **/
	public function selectWithParams($stmtSql, $params = array())
	{
		return $this->execute($stmtSql, $params, true);
	}

	/**
	 * Creates new SQL transaction
	 * @return Database_Component
	 **/
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

	/**
	 * Adds SQL query to transaction
	 * @param string $sql
	 * @return Database_Component
	 **/
	public function transact($sql)
	{
		if (!$this->m_pdo)
			return $this;

		if ($this->m_pdo->exec($sql) === false)
			$this->m_transaction['errors'] = true;

		return $this;
	}

	/**
	 * Tries to commit active transaction
	 * @return Database_Component
	 **/
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

	/**
	 * Returns some specific transaction info
	 * @param string $info
	 * @return mixed
	 **/
	public function getTranscationInfo($info)
	{
		return isset($this->m_transaction[$info]) ? $this->m_transaction[$info] : null;
	}

	/**
	 * Sets indexing key for query results
	 * If $multiply == true, all results will be merged in one sub-array
	 * with the same key as provided indexing key
	 * @param string $field
	 * @param string $multiply = false
	 * @return Database_Component
	 **/
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

	/**
	 * Returns last SQL query result (that was fetched)
	 * @return array
	 **/
	public function getData()
	{
		return $this->m_resultData;
	}

	/**
	 * Closes connection with DB
	 * @return Database_Component
	 **/
	public function disconnect()
	{
		$this->m_pdo = null;

		return $this;
	}

	/**
	 * Returns last insert ID
	 * @return int
	 **/
	public function getInsertId()
	{
		return $this->m_lastInsertId;
	}
};