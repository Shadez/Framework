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

	/**
	 * Sets region name
	 * @param string $name
	 * @return Region_Component
	 **/
	public function setName($name)
	{
		$this->m_regionName = $name;

		return $this;
	}

	/**
	 * Returns region name
	 * @return string
	 **/
	public function getName()
	{
		return $this->m_regionName;
	}

	/**
	 * Checks if block exists in current region
	 * @param string $name
	 * @return bool
	 **/
	public function blockExists($name)
	{
		return isset($this->m_regionBlocks[$name]);
	}

	/**
	 * Adds block to region
	 * @param Block_Component $block
	 * @return Region_Component
	 **/
	public function addBlock(Block_Component $block)
	{
		if ($this->blockExists($block->getName()))
			return $this;

		$this->m_regionBlocks[$block->getName()] = $block;

		return $this;
	}

	/**
	 * Returns Block_Component by it's name
	 * @param mixed $block
	 * @return Block_Component
	 **/
	public function getBlock($block)
	{
		if (is_object($block) && isset($this->m_regionBlocks[$block->getName()]))
			return $this->m_regionBlocks[$block->getName()];
		elseif (is_string($block) && isset($this->m_regionBlocks[$block]))
			return $this->m_regionBlocks[$block];

		return null;
	}

	/**
	 * Removes block from region
	 * @param mixed $block
	 * @return Region_Component
	 **/
	public function removeBlock($block)
	{
		if (is_object($block) && isset($this->m_regionBlocks[$block->getName()]))
			unset($this->m_regionBlocks[$block->getName()]);
		elseif (is_string($block) && isset($this->m_regionBlocks[$block]))
			unset($this->m_regionBlocks[$block]);

		return $this;
	}

	/**
	 * Returns all region's blocks
	 * @return array
	 **/
	public function getBlocks()
	{
		return $this->m_regionBlocks;
	}

	/**
	 * Renders region
	 * @param array $vars
	 * @return Region_Component
	 **/
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

	/**
	 * Returns region's HTML content
	 * @return string
	 **/
	public function getRegionHTML()
	{
		return $this->m_regionHtml;
	}
};