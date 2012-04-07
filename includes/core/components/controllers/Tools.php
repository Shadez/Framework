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

class Tools_Controller_Component extends Controller_Component
{
	private $m_action = '';

	public function build($core)
	{
		if ($core->getUrlAction(1) != null)
			$this->m_action = $core->getUrlAction(1);

		if (in_array($this->m_action, array('models')))
		{
			return $this->delegateTo(ucfirst(strtolower($this->m_action)) . '_Tools');
		}

		$this->buildBlock('tools');

		return $this;
	}

	protected function block_tools()
	{
		return $this->block()
			->setTemplate('tools', 'default')
			->setRegion('pagecontent');
	}
}