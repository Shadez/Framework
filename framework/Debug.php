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

class SFDebug
{
	private $m_generationTime = 0.0;
	private $m_memoryUsage = 0.0;
	private $m_memoryUsagePeak = 0.0;
	private $m_sqlQueriesCount = 0;
	private $m_sqlQueriesTiming = 0.0;
	private $m_componentsCount = 0;
	private $m_componentsList = array();
	private $m_debugHTML = '';
	private $m_data = array();

	private $core = null;

	public function __construct(Core $core)
	{
		if (!$core)
			throw new Exception('core was not found');

		$this->core = $core;
	}

	public function setData($data)
	{
		$this->m_data = $data;
		dump($data);

		$this->debug();
	}

	public function getResult()
	{
		return $this->m_debugHTML;
	}

	private function debug()
	{
		
	}
}