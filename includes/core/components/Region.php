<?php

/**
 * Copyright (C) 2009-2011 Shadez <https://github.com/Shadez>
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
	private $m_regionBlockNames = array();

	/**
	 * Sets region name
	 * @access public
	 * @param  $region_name
	 * @return Region_Component
	 **/
	public function setRegionName($region_name)
	{
		$this->m_regionName = $region_name;
		$this->m_regionBlocks = array();

		return $this;
	}

	/**
	 * Checks whether block exists
	 * @access private
	 * @param  string $blockName
	 * @return bool
	 **/
	private function blockExists($blockName)
	{
		return in_array($blockName, $this->m_regionBlockNames) && isset($this->m_regionBlocks[$blockName]);
	}

	/**
	 * Assigns $block block to current region (if not assinged yet)
	 * @access public
	 * @param  Block_Component $block
	 * @return Region_Component
	 **/
	public function addBlock($block)
	{
		if ($this->blockExists($block->getBlockName()))
			return $this; // Already added

		$this->m_regionBlocks[$block->getBlockName()] = $block;
		$this->m_regionBlockNames[] = $block->getBlockName();

		return $this;
	}

	/**
	 * Returns block by name
	 * @access public
	 * @param  string $blockName
	 * @return Component
	 **/
	public function getBlock($blockName)
	{
		if (!$this->blockExists($blockName))
			return $this->c('Null');

		return $this->m_regionBlocks[$blockName];
	}

	/**
	 * Returns all blocks for current region
	 * @access public
	 * @return array
	 **/
	public function getAllBlocks()
	{
		return $this->m_regionBlocks;
	}

	/**
	 **/
	public function renderAllBlocks()
	{
		foreach ($this->m_regionBlocks as &$block)
		{
			if ($block->getBlockState())
				$block->render();
		}
	}
}