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

class Document_Component extends Component
{
	private $m_regions = array();
	private $m_clientCss = array();
	private $m_clientJs = array();

	private function addRegion($name)
	{
		if ($this->regionExists($name))
			return $this->getRegion($name);

		$this->m_regions[$name] = new Region_Component('region', $this->core);
		$this->m_regions[$name]->setRegionName($name);

		return $this->m_regions[$name];
	}

	public function getRegion($regionName)
	{
		if (!$regionName)
			$regionName = 'content';

		if (!$this->regionExists($regionName))
			return $this->addRegion($regionName);

		return $this->m_regions[$regionName];
	}

	public function getAllRegions()
	{
		return $this->m_regions;
	}

	public function regionExists($name)
	{
		return isset($this->m_regions[$name]);
	}

	public function setCSS($css)
	{
		$this->m_clientCss = $css;

		return $this;
	}

	public function setJS($js)
	{
		$this->m_clientJs = $js;

		return $this;
	}

	public function registerCss($css, $type = 'header')
	{
		if (!isset($this->m_clientCss[$type]))
			$this->m_clientCss[$type] = array();

		$this->m_clientCss[$type][] = $css;

		return $this;
	}

	public function registerJs($js, $type = 'header')
	{
		if (!isset($this->m_clientJs[$type]))
			$this->m_clientJs[$type] = array();

		$this->m_clientJs[$type][] = $js;

		return $this;
	}

	public function releaseCss($type = 'header')
	{
		if (!isset($this->m_clientCss[$type]))
			return '';

		$files_string = '';

		foreach ($this->m_clientCss[$type] as &$css)
		{
			if (!isset($css['file']) && isset($css['style']))
			{
				$files_string .= '<style type="text/css">' . NL . $css['style'] . NL . '</style>' . NL;
			}
			elseif (isset($css['file']))
			{
				if (isset($css['browser']) && $css['browser'])
					$files_string .= '<!--[if ' . $css['browser'] . ']>' . NL;

				$files_string .= '<link rel="stylesheet" type="text/css" href="' . (isset($css['external']) ? $css['file'] : $this->getCFP($css['file'])) . (isset($css['version']) ? '?v=' . $css['version'] : '') . '" media="' . (isset($css['media']) ? $css['media'] : 'all') . '" />' . NL;

				if (isset($css['browser']) && $css['browser'])
					$files_string .= '<![endif]-->' . NL;
			}
		}

		return $files_string;
	}

	public function releaseJs($type = 'header')
	{
		if (!isset($this->m_clientJs[$type]))
			return '';

		$files_string = '';

		foreach ($this->m_clientJs[$type] as &$js)
		{
			if (isset($js['file']))
				$files_string .= '<script language="javascript" type="text/javascript" src="' . (isset($js['external']) ? $js['file'] : $this->getCFP($js['file'])) . (isset($js['version']) ? '?v=' . $js['version'] : '') . '"></script>' . NL;
			elseif (isset($js['code']))
				$files_string .= '<script language="javascript" type="text/javascript">' . NL . '    ' . $js['code'] . NL . '</script>' . NL;
		}

		return $files_string;
	}
}