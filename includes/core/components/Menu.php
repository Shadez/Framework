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

class Menu_Component extends Component
{
	protected $m_menu = array();
	protected $m_path = 'wow';

	public function initialize()
	{
		$this->m_menu = $this->c('QueryResult', 'Db')
			->model('Menu')
			->fieldCondition('wow_main_menu.path', '= \'' . $this->m_path . '\'')
			->loadItems();

		$this->handleMenu();

		return $this;
	}

	private function getCurrentAction()
	{
		$url_actions = $this->core->getActions();
		if (!$url_actions)
			return null;

		$path_found = false;
		foreach ($url_actions as &$action)
		{
			if ($path_found)
				return '/' . $action;

			if ($action == $this->m_path && !$path_found)
				$path_found = true;
		}

		return '/';
	}

	/**
	 * Parses menu and sets active item
	 * @access protected
	 * @param  string $menu_index = null
	 * @return Menu_Component
	 **/
	protected function handleMenu($menu_index = null)
	{
		if (!$this->m_menu)
			return $this;

		$current_action = $this->getCurrentAction();
		foreach ($this->m_menu as &$item)
		{
			if (($current_action == $item['href']) || ($current_action == $item['page_index'] || ($menu_index && $menu_index == $item['page_index'])))
			{
				$item['active'] = true;
				if ($item['href'] != '/')
					$this->c('Layout')->setMenuTitle($item['title']);
			}
			else
				$item['active'] = false;
		}

		return $this;
	}

	/**
	 * Returns handled menu
	 * @access public
	 * @return array
	 **/
	public function getMenu($activeIndex = null)
	{
		if ($activeIndex)
			return $this->handleMenu($activeIndex)->m_menu;
			
		return $this->m_menu;
	}
}