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

class ControllerGroups_Component extends Component
{
	protected $m_controllerGroups = array();

	public function initialize()
	{
		$groups = $this->c('Config')->getValue('controller.groups');

		if (!$groups)
			return $this;

		foreach ($groups as $name => $group)
		{
			if ($group)
				$this->m_controllerGroups[$name] = $group;
		}

		return $this;
	}

	public function addBlocksToGroup($group, $blocks)
	{
		if (!$group || !$blocks)
			return $this;

		if (!isset($this->m_controllerGroups[$group]))
			$this->m_controllerGroups[$group] = array('info' => array(), 'blocks' => array());

		$this->m_controllerGroups[$group]['blocks'] = array_merge($this->m_controllerGroups[$group]['blocks'], $blocks);

		return $this;
	}

	public function getBlocksFromGroup($group)
	{
		return (isset($this->m_controllerGroups[$group]) && $this->m_controllerGroups[$group]['blocks'] ? $this->m_controllerGroups[$group]['blocks'] : array());
	}

	public function setGroupLayout($group, $layout)
	{
		if (!$group || !$layout)
			return $this;

		if (!isset($this->m_controllerGroups[$group]))
			$this->m_controllerGroups[$group] = array(
				'info' => array(
					'layout' => array()
				),
				'blocks' => array()
			);

		$this->m_controllerGroups[$group]['info']['layout'] = $layout;

		return $this;
	}

	public function getGroupLayout($group)
	{
		return (isset($this->m_controllerGroups[$group], $this->m_controllerGroups[$group]['info'], $this->m_controllerGroups[$group]['info']['layout'])) ? $this->m_controllerGroups[$group]['info']['layout'] : $this->c('Config')->getValue('controller.default_layout');
	}

	public function setBlockVar($var, $value, $group = '_all_', $block = '_all_')
	{
		if (!$var || !$group || !$block)
			return $this;

		foreach ($this->m_controllerGroups as $grName => &$gr)
		{
			if ($grName == $group || $group == '_all_')
			{
				if (!$gr || !isset($gr['blocks']) || !$gr['blocks'])
					continue;

				foreach ($gr['blocks'] as $blName => &$bl)
				{
					if ($blName == $block || $block == '_all_')
					{
						if (!isset($bl['vars']))
							$bl['vars'] = array();

						$bl['vars'][$var] = $value;
					}
				}
			}
		}

		return $this;
	}
};