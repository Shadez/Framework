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

	public function registerCss($css, $type = 'header')
	{
		if (!is_array($css) || !$type)
			return $this;

		if (!isset($this->m_clientCss[$type]))
			$this->m_clientCss[$type] = array();

		if (!isset($css['file']))
		{
			// Multiply css files
			foreach ($css as $key => $file)
			{
				if (!isset($file['file']))
					continue;

				$this->m_clientCss[$type][$key] = $file;
			}
		}
		else
			$this->m_clientCss[$type][$css['file']] = $css;

		return $this;
	}

	public function registerJs($js, $type = 'header')
	{
		if (!is_array($js) || !$type)
			return $this;

		if (!isset($this->m_clientJs[$type]))
			$this->m_clientJs[$type] = array();

		if (!isset($js['file']))
		{
			// Multiply css files
			foreach ($js as $key => $file)
			{
				if (!isset($file['file']))
					continue;

				$this->m_clientJs[$type][$key] = $file;
			}
		}
		else
			$this->m_clientJs[$type][$js['file']] = $js;

		return $this;
	}

	public function releaseCss($type = 'header')
	{
		if (!isset($this->m_clientCss[$type]))
			return '';

		$files_string = '';

		foreach ($this->m_clientCss[$type] as &$css)
		{
			if (isset($css['browser']) && $css['browser'])
				$files_string .= '<!--[if ' . $css['browser'] . ']>' . NL;

			$files_string .= '<link rel="stylesheet" type="text/css" href="' . (!isset($css['external']) ? $css['file'] : $this->getPath($css['file'])) . (isset($css['version']) ? '?v=' . $css['version'] : '') . '" media="' . (isset($css['media']) ? $css['media'] : 'all') . '" />' . NL;

			if (isset($css['browser']) && $css['browser'])
				$files_string .= '<![endif]-->' . NL;
		}

		return $files_string;
	}

	public function releaseJs($type = 'header')
	{
		if (!isset($this->m_clientJs[$type]))
			return '';

		$files_string = '';

		foreach ($this->m_clientJs[$type] as &$js)
			$files_string .= '<script language="javascript" type="text/javascript" src="' . (!isset($js['external']) ? $js['file'] : $this->getPath($js['file'])) . (isset($js['version']) ? '?v=' . $js['version'] : '') . '"></script>' . NL;

		return $files_string;
	}
}