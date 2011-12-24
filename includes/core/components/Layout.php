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

class Layout_Component extends Component
{
	private $m_css = array();
	private $m_js = array();
	protected $m_pageTitle = '';
	protected $m_menuTitle = '';

	public function setMenuTitle($title)
	{
		$this->m_menuTitle = $title;

		return $this;
	}

	public function setPageTitle($title)
	{
		$this->m_pageTitle = $title;

		return $this;
	}

	public function getPageTitle()
	{
		return $this->m_pageTitle;
	}

	public function initialize()
	{
		$ClientCSS = array();
		$ClientJS = array();

		include(SITE_DIR . 'layouts' . DS . 'ClientCss.php');
		include(SITE_DIR . 'layouts' . DS . 'ClientJs.php');

		$this->m_css = $ClientCSS;
		$this->m_js = $ClientJS;

		unset($ClientCSS, $ClientJS);

		return $this;
	}

	protected function checkCondition(&$files, &$aHolder)
	{
		if (!$files)
			return false;

		$aHolder = array();

		foreach ($files as $region => $holder)
		{
			if (!isset($aHolder[$region]))
				$aHolder[$region] = array();

			foreach ($holder as $file)
			{
				$aHolder[$region][] = $file;
			}
		}

		return $this;
	}

	public function getControllerCss($controller)
	{
		if (!isset($this->m_css[$controller]))
			return null;

		$css = $this->m_css[$controller];

		$sendTo = array();

		$this->checkCondition($css, $sendTo);

		unset($css);

		return $sendTo;
	}

	public function getControllerJs($controller)
	{
		if (!isset($this->m_js[$controller]))
			return null;

		$js = $this->m_js[$controller];

		$sendTo = array();

		$this->checkCondition($js, $sendTo);

		unset($js);

		return $sendTo;
	}
}