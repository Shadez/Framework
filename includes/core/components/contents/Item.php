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

class Item_Content_Component extends Component
{
	protected $m_content = array();

	public function loadContentByUrl()
	{
		$url = implode('/', $this->core->getActions());

		if (!$url || $url == 'favicon.ico')
			return $this;

		$content = $this->c('QueryResult', 'Db')
			->model('Contents')
			->fieldCondition('url', ' = \'' . $url . '\'')
			->order(array('Contents' => array('locale')))
			->loadItems();

		if (!$content)
			return $this;

		$use_content = array();

		foreach ($content as $item)
			if ($item['locale'] == $this->c('Locale')->GetLocaleID())
				$use_content = $item;

		if (!$use_content)
			$use_content = $content[0]; // Select default

		$this->m_content = $use_content;

		return $this;
	}

	public function loadContentById($id)
	{
		$this->m_content = $this->c('QueryResult', 'Db')
			->model('Contents')
			->setItemId($id)
			->loadItem();

		return $this;
	}

	public function contentLoaded()
	{
		return $this->m_content ? true : false;
	}

	public function getContent()
	{
		return $this->m_content;
	}
}