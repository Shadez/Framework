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

class ResultHolder_Db_Component extends Component
{
	private $m_data = array();
	private $m_dataMap = array();
	private $m_dataSize = 0;
	private $m_rowIndex = 0;
	private $m_sqlQuery = '';
	private $m_assigned = false;
	private $m_offsetUsed = false;
	private $m_iterators = array();
	private $m_iteratorId = 0;

	public function setResult($data, $query)
	{
		$this->m_data = $data;

		// Generate data map (to access indexes)

		$this->m_dataMap = array_keys($data);

		$this->m_dataSize = sizeof($data);
		$this->m_rowIndex = 0;
		$this->m_sqlQuery = $query;

		$this->setIterators();

		unset($data, $query);

		return $this->setAssigned(true);
	}

	private function setIterators()
	{
		$this->m_iterators = array();

		foreach ($this->m_dataMap as $idx => $key)
			$this->m_iterators[$idx] = $this->i('ResultIterator', 'Db')
				->setData($this->m_data[$key]);

		$this->m_iterators[$this->m_dataSize] = $this->i('ResultIterator', 'Db')->lastIterator();

		return $this;
	}

	private function setAssigned($assigned)
	{
		$this->m_assigned = (bool) $assigned;

		return $this;
	}

	public function next($startWithOffset = false)
	{
		if ($startWithOffset && $this->m_rowIndex == 0 && !$this->m_offsetUsed)
		{
			$this->m_offsetUsed = true;
			$this->m_rowIndex = -1;
		}

		if (($this->m_rowIndex + 1) < $this->m_dataSize)
		{
			$this->m_rowIndex += 1;

			return true;
		}

		return false;
	}

	public function getRow()
	{
		if (!isset($this->m_dataMap[$this->m_rowIndex]))
			return null;

		if (!isset($this->m_data[$this->m_dataMap[$this->m_rowIndex]]))
			return null;

		return $this->m_data[$this->m_dataMap[$this->m_rowIndex]];
	}

	public function begin()
	{
		return $this->m_iterators[0];
	}

	public function end()
	{
		return $this->m_iterators[$this->m_dataSize];
	}

	public function iter($toNext = true)
	{
		if ($toNext)
			$this->m_iteratorId++;

		return $this->m_iterators[$this->m_iteratorId];
	}

	public function rewind($id = 0)
	{
		$this->m_iteratorId = min($id, $this->m_dataSize-1);

		return $this;
	}

	public function getRowField($field)
	{
		$row = $this->getRow();

		if (!$row)
			return null;

		if (isset($row[$field]))
			return $row[$field];

		return null;
	}

	public function reset()
	{
		$this->m_rowIndex = 0;
		$this->m_offsetUsed = false;

		return $this;
	}

	public function free()
	{
		$this->m_data = array();
		$this->m_dataMap = array();
		$this->m_rowIndex = 0;
		$this->m_sqlQuery = '';
		$this->m_dataSize = 0;
		$this->m_assigned = false;

		return $this;
	}

	public function getQuery()
	{
		return $this->m_sqlQuery;
	}

	public function getRowsCount()
	{
		return $this->m_dataSize;
	}

	public function getData()
	{
		return $this->m_data;
	}

	public function getFieldValues($field)
	{
		$data = array();

		foreach ($this->m_data as $row)
			foreach ($row as $f => $v)
				if ($f == $field)
					$data[] = $v;

		return $data;
	}
}