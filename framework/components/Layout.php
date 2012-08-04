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

class Layout extends Component
{
	private $m_css = array();
	private $m_js = array();
	private $m_pageTitle = '';
	private $m_pageMetaTags = array();
	private $m_breadcrumb = '';

	public function initialize()
	{
		$this->m_css = array();
		$this->m_js = array();
		$this->m_pageTitle = $this->c('Config')->getValue('app.layout.title');
		$this->m_pageMetaTags = $this->c('Config')->getValue('app.layout.metatags');

		return $this;
	}

	/**
	 * Sets bread crumb data
	 * @param array $bc
	 * @return Layout_Component
	 **/
	public function setBreadcrumb($bc)
	{
		if (!$bc)
			return $this;

		$this->m_breadcrumb = '';

		$size = sizeof($bc);

		for ($i = 0; $i < $size; ++$i)
		{
			if (!$bc[$i])
				continue;

			$this->m_breadcrumb .= ($i < $size - 1 ? '<a href="' . $this->getCore()->getUrl($bc[$i]['link']) . '">' : '<strong>') . $this->c('I18n')->getString($bc[$i]['caption']) . ($i < $size - 1 ? '</a> &raquo; ' : '</strong>');
		}

		return $this;
	}

	/**
	 * Loads client files (CSS/JS) according to active controller or it's client files group
	 * @return Layout_Component
	 **/
	public function loadClientFiles()
	{
		if ($this->m_css || $this->m_js)
			return $this;

		require_once(APP_LAYOUTS_DIR . 'ClientCss.' . PHP_EXT);
		require_once(APP_LAYOUTS_DIR . 'ClientJs.' . PHP_EXT);

		$controller_name = $this->getCore()->getActiveController()->getClientFilesControllerName();
		$controller_action = $this->getCore()->getActiveController()->getControllerAction();

		if (!$controller_name)
		{
			unset($ClientCss, $ClientJs);

			return $this;
		}

		foreach (array('css' => $ClientCss, 'js' => $ClientJs) as $type => $cf)
		{
			if (!$cf)
				continue;

			foreach ($cf as $cname => $cactions)
			{
				if (!$cactions)
					continue;

				if ($cname == '_overall_')
					$this->addClientFiles($type, $cactions);
				elseif ($cname == $controller_name)
				{
					foreach ($cactions as $region => $files)
						if ($region == $controller_action || $region == '_overall_')
							$this->addClientFiles($type, $files);
				}
			}
		}

		unset($ClientCss, $ClientJs);

		return $this;
	}

	/**
	 * Adds some client files to $type type
	 * @param string $type
	 * @param array $files
	 * @return Layout_Component
	 **/
	public function addClientFiles($type, $files)
	{
		if (!$files)
			return $this;

		foreach ($files as $region => $f)
			if (isset($this->{'m_' . $type}[$region]))
				$this->{'m_' . $type}[$region] = array_merge($this->{'m_' . $type}[$region], $f);
			else
				$this->{'m_' . $type}[$region] = $f;

		return $this;
	}

	/**
	 * Returns HTML string for CSS output
	 * @param string $region = 'header'
	 * @return string
	 **/
	public function releaseCss($region = 'header')
	{
		if (!isset($this->m_css[$region]))
			return '';

		$files_string = '';

		foreach ($this->m_css[$region] as $css)
		{
			if (!isset($css['file']) && isset($css['style']))
			{
				if (isset($css['browser']) && $css['browser'])
					$files_string .= '<!--[if ' . $css['browser'] . ']>' . NL;

				$files_string .= '<style type="text/css">' . NL . $css['style'] . NL . '</style>' . NL;

				if (isset($css['browser']) && $css['browser'])
					$files_string .= '<![endif]-->' . NL;
			}
			elseif (isset($css['file']))
			{
				if (isset($css['browser']) && $css['browser'])
					$files_string .= '<!--[if ' . $css['browser'] . ']>' . NL;

				if (isset($css['wrapper']) && $css['wrapper'])
					$files_string .= '<' . $css['wrapper'] . '>' . NL . TAB;

				$files_string .= '<link rel="stylesheet" type="text/css" href="' . (isset($css['external']) ? $css['file'] : $this->getCore()->getPath($css['file'])) . (isset($css['version']) ? '?v=' . $css['version'] : '') . '" media="' . (isset($css['media']) ? $css['media'] : 'all') . '" />' . NL;

				if (isset($css['wrapper']) && $css['wrapper'])
					$files_string .= '</' . $css['wrapper'] . '>'. NL;

				if (isset($css['browser']) && $css['browser'])
					$files_string .= '<![endif]-->' . NL;
			}
		}

		return $files_string;
	}

	/**
	 * Returns HTML string for JS output
	 * @param string $region = 'header'
	 * @return string
	 **/
	public function releaseJs($region = 'header')
	{
		if (!isset($this->m_js[$region]))
			return '';

		$files_string = '';

		foreach ($this->m_js[$region] as $js)
		{
			if (isset($js['browser']) && $js['browser'])
				$files_string .= '<!--[if ' . $js['browser'] . ']>' . NL;
			if (isset($js['file']))
				$files_string .= '<script language="javascript" type="text/javascript" src="' . (isset($js['external']) ? $js['file'] : $this->getCore()->getPath($js['file'])) . (isset($js['version']) ? '?v=' . $js['version'] : '') . '"></script>' . NL;
			elseif (isset($js['code']))
				$files_string .= '<script language="javascript" type="text/javascript">' . NL . '    ' . $js['code'] . NL . '</script>' . NL;
			if (isset($js['browser']) && $js['browser'])
				$files_string .= '<![endif]-->' . NL;
		}

		return $files_string;
	}

	/**
	 * Returns HTML string for meta-tags output (all if $type == '')
	 * @param string $type = ''
	 * @return string
	 **/
	public function releaseMetaTags($type = '')
	{
		if (!$this->m_pageMetaTags)
			return '';

		$tags_string = '';

		if (!$type)
		{
			foreach ($this->m_pageMetaTags as $name => $tag)
				$tags_string .= '<meta ' . (isset($tag['name']) ? $tag['name'] . '="' . $name . '"' : 'name="' . $name. '"') . ' content="' . $tag['content'] . '" />' . NL;
		}
		elseif (isset($this->m_pageMetaTags[$type]))
		{
			$tag = $this->m_pageMetaTags[$type];
			$tags_string = '<meta ' . (isset($tag['name']) ? $tag['name'] . '="' . $type . '"' : 'name="' . $type. '"') . ' content="' . $tag['content'] . '" />' . NL;
		}

		return $tags_string;
	}

	/**
	 * Adds meta tag
	 * @param string $name
	 * @param string $content
	 * @param string $typeName = ''
	 * @return Layout_Component
	 **/
	public function setMetaTag($name, $content, $typeName = '')
	{
		$this->m_pageMetaTags[$name] = array();

		if ($typeName)
			$this->m_pageMetaTags[$name]['name'] = $typeName;

		$this->m_pageMetaTags[$name]['content'] = $content;

		return $this;
	}

	/**
	 * Sets meta tags
	 * @param array $tags
	 * @return Layout_Component
	 **/
	public function setMetaTags($tags)
	{
		if (!$tags)
			return $this;

		foreach ($tags as $tag)
		{
			if (!$tag || !isset($tag['name'], $tag['content']))
				continue;

			$this->setMetaTag($tag['name'], $tag['content'], isset($tag['typeName']) ? $tag['typeName'] : null);
		}

		return $this;
	}

	/**
	 * Removes meta tag
	 * @param string $name
	 * @return Layout_Component
	 **/
	public function removeMetaTag($name)
	{
		if (isset($this->m_pageMetaTags[$name]))
			unset($this->m_pageMetaTags[$name]);

		return $this;
	}

	/**
	 * Removes meta tags (all if $tags == array())
	 * @param array $tags = array()
	 * @return Layout_Component
	 **/
	public function removeMetaTags($tags = array())
	{
		if (!$tags)
		{
			$this->m_pageMetaTags = array();

			return $this;
		}

		foreach ($tags as $tag)
			$this->removeMetaTag($tag);

		return $this;
	}

	/**
	 * Sets page title (if $append == false, it will replace existing title)
	 * @param string $title
	 * @param bool $append = true
	 * @return Layout_Component
	 **/
	public function setPageTitle($title, $append = true)
	{
		if (!$title)
			return $this;

		if (!$append)
			$this->m_pageTitle = $title;
		else
		{
			if (is_array($title))
				$this->m_pageTitle = implode($this->c('Config')->getValue('app.layout.title_delimiter'), array_merge($title, array($this->m_pageTitle)));
			else
				$this->m_pageTitle = $this->c('I18n')->getString($title) . $this->c('Config')->getValue('app.layout.title_delimiter') . $this->m_pageTitle;
		}

		return $this;
	}

	/**
	 * Returns page title
	 * @return string
	 **/
	public function getPageTitle()
	{
		return $this->m_pageTitle;
	}

	/**
	 * Returns breadcrumb HTML
	 * @return string
	 **/
	public function getBreadcrumb()
	{
		return $this->m_breadcrumb;
	}
}