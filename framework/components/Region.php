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

class Region_Component extends Component
{
	private $m_regionName = '';
	private $m_regionBlocks = array();
	private $m_regionHtml = '';
	private $m_regionState = false;

	public function initialize()
	{
		$this->m_regionState = false;

		return $this;
	}

	public function setName($name)
	{
		$this->m_regionName = $name;

		return $this;
	}

	public function getName()
	{
		return $this->m_regionName;
	}

	public function blockExists($name)
	{
		return isset($this->m_regionBlocks[$name]);
	}

	public function addBlock(Block_Component $block)
	{
		if ($this->blockExists($block->getName()))
			return $this;

		$this->m_regionBlocks[$block->getName()] = $block;

		return $this;
	}

	public function getBlock($block)
	{
		if (is_object($block) && isset($this->m_regionBlocks[$block->getName()]))
			return $this->m_regionBlocks[$block->getName()];
		elseif (is_string($block) && isset($this->m_regionBlocks[$block]))
			return $this->m_regionBlocks[$block];

		return null;
	}

	public function removeBlock($block)
	{
		if (is_object($block) && isset($this->m_regionBlocks[$block->getName()]))
			unset($this->m_regionBlocks[$block->getName()]);
		elseif (is_string($block) && isset($this->m_regionBlocks[$block]))
			unset($this->m_regionBlocks[$block]);

		return $this;
	}

	public function getBlocks()
	{
		return $this->m_regionBlocks;
	}

	public function renderRegion($vars)
	{
		if ($this->m_regionState)
			return $this;

		$this->m_regionHtml = '';

		foreach ($this->m_regionBlocks as $block)
			$this->m_regionHtml .= $block->renderBlock($vars)->getBlockHTML();

		$this->m_regionState = true;

		return $this;
	}

	public function getRegionHTML()
	{
		return $this->m_regionHtml;
	}
};