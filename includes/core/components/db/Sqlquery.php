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

class SqlQuery_Db_Component extends Component
{
	protected $m_data = array();

	public function selectItem($sql, $db_type, $keyIndex = '')
	{
		return $this->_query($sql, $db_type, 'selectRow', $keyIndex);
	}

	protected function _query($sql, $db_type, $type, $keyIndex = '')
	{
		$query = array($sql);

		if ($keyIndex != '' && $this->c('Db')->isDatabaseAvailable($db_type))
			$this->c('Db')->{$db_type}()->IndexResults($keyIndex);

		if ($this->c('Db')->isDatabaseAvailable($db_type))
			$this->m_data = call_user_func_array(array($this->c('Db')->{$db_type}(), $type), $query);
		else
			$this->m_data = false;

		return $this;
	}

	public function selectItems($sql, $db_type, $keyIndex = '')
	{
		return $this->_query($sql, $db_type, 'select', $keyIndex);
	}

	public function getData()
	{
		return $this->m_data;
	}
}