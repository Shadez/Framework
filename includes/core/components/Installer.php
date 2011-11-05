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

class Installer_Component extends Component
{
	private $m_currentStep = 0;
	private $m_installationInfo = array();
	private $m_isInstallationAllowed = false;

	public function initialize()
	{
		if ($this->core->isInstalled())
			return $this;

		$this->m_installationAllowed = true;
		$this->m_currentStep = intval($this->core->getUrlAction(1));

		/*
		 Step 0: engine info
		 Step 1: license info
		 Step 2: environment check
		 Step 3: 
		*/
		return $this;
	}

	public function isAllowed()
	{
		return !$this->core->isInstalled() && $this->m_installationAllowed;
	}
}