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

class Layout_Component extends Component
{
	private $m_css = array();
	private $m_js = array();
	protected $m_pageTitle = '';
	protected $m_menuTitle = '';
	protected $m_siteTitle = '';

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
		$title = '';

		if ($this->m_menuTitle)
			$title = $this->m_menuTitle;

		if ($this->m_pageTitle)
			$title .= ($title ? ' :: ' : '') . $this->m_pageTitle;

		if ($this->m_siteTitle)
			$title .= ($title ? ' :: ' : '') . $this->m_siteTitle;

		return $title;
	}

	public function initialize()
	{
		$ClientCSS = array();
		$ClientJS = array();

		require_once(SITE_DIR . 'layouts' . DS . 'ClientCss.php');
		require_once(SITE_DIR . 'layouts' . DS . 'ClientJs.php');

		$this->m_css = $ClientCSS;
		$this->m_js = $ClientJS;

		unset($ClientCSS, $ClientJS);

		$this->m_siteTitle = $this->c('Config')->getValue('site.title');

		return $this;
	}

	public function loadClientFiles(&$css, &$js, $action)
	{
		$client_files = array(
			'css' => array(),
			'js'  => array()
		);

		$file_types = array('_overall');

		if ($this->core->getUrlAction(1))
			$file_types[] = $this->core->getUrlAction(1);

		$all_files = array('css' => $this->m_css, 'js' => $this->m_js);

		foreach ($all_files as $fileType => $holder)
		{
			if (isset($holder['_overall']))
			{
				foreach ($holder['_overall'] as $type => $files)
				{
					if (!isset($client_files[$fileType][$type]))
						$client_files[$fileType][$type] = array();

					foreach ($files as $f)
						$client_files[$fileType][$type][] = $f;
				}
			}

			if ($action && isset($holder[$action]))
			{
				foreach ($holder[$action] as $subAction => $types)
				{
					if (in_array($subAction, $file_types))
					{
						foreach ($types as $type => $files)
						{
							if (!isset($client_files[$fileType][$type]))
								$client_files[$fileType][$type] = array();

							foreach ($files as $f)
								$client_files[$fileType][$type][] = $f;
						}
					}
				}
			}
		}

		$css = $client_files['css'];
		$js = $client_files['js'];

		return $this;
	}
}