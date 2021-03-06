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

namespace Db;
class ResultIterator
{
	private $m_last = false;
	private $m_guid = 0;
	private $m_data = array();
	private $m_field = '';
	private $m_fieldCast = 0;

	const RAND_1 = 0.45;
	const RAND_2 = 0.22;

	/**
	 * Sets field to be returned
	 * @param string $field
	 * @return ResultIterator_Db_Component
	 **/
	public function __get($field)
	{
		if ($this->m_last)
			return null;

		if (isset($this->m_data[$f]))
			$this->m_field = $f;

		return $this;
	}

	/**
	 * Casts field to appropriate type
	 * @return mixed
	 **/
	public function __toString()
	{
		if ($this->m_field && isset($this->m_data[$this->m_field]))
		{
			switch ($this->m_fieldCast)
			{
				case 1:
					return (int) $this->m_data[$this->m_field];
				case 2:
					return (float) $this->m_data[$this->m_field];
				case 3:
					return (double) $this->m_data[$this->m_field];
				case 4:
					return (bool) $this->m_data[$this->m_field];
				case 5:
				default:
					return (string) $this->m_data[$this->m_field];
			}
		}
		elseif ($this->m_data[$this->m_field])
			return $this->m_data[$this->m_field];

		return null;
	}

	/**
	 * Implements pseudo-methods for field cast (toInt, toFloat, toDouble, toBool, toString)
	 * @param string $method
	 * @param array $args
	 * @return mixed
	 **/
	public function __call($method, $args)
	{
		$method = strtolower($method);

		if (!$this->m_field || !isset($this->m_data[$this->m_field]))
			return null;

		switch ($method)
		{
			case 'toint':
				$this->m_fieldCast = 1;
				return (int) $this->m_data[$this->m_field];
			case 'tofloat':
				$this->m_fieldCast = 2;
				return (float) $this->m_data[$this->m_field];
			case 'todouble':
				$this->m_fieldCast = 3;
				return (double) $this->m_data[$this->m_field];
			case 'tobool':
				$this->m_fieldCast = 4;
				return (bool) $this->m_data[$this->m_field];
			case 'tostring':
			default:
				$this->m_fieldCast = 5;
				return (string) $this->m_data[$this->m_field];
		}

		return null;
	}

	public function initialize()
	{
		$this->m_guid = round(mt_rand() * self::RAND_1 - mt_rand() * self::RAND_2);

		return $this;
	}

	public function setInitialized()
	{
		return $this;
	}

	/**
	 * Sets query result
	 * @param array $data
	 * @return ResultIterator_Db_Component
	 **/
	public function setData($data)
	{
		if ($this->m_last)
			return $this;

		$this->m_data = $data;

		return $this;
	}

	/**
	 * Sets current iterator as last iterator in vector
	 * @return ResultIterator_Db_Component
	 **/
	public function lastIterator()
	{
		$this->m_last = true;

		return $this;
	}

	/**
	 * Sets field to be returned
	 * @param string $field
	 * @return ResultIterator_Db_Component
	 **/
	public function field($f)
	{
		if ($this->m_last)
			return null;

		if (isset($this->m_data[$f]))
			$this->m_field = $f;

		return $this;
	}

	/**
	 * Returns raw iterator data
	 * @return array
	 **/
	public function getData()
	{
		return $this->m_data;
	}
};