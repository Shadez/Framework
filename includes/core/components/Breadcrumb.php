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

class Breadcrumb_Component extends Component
{
	protected $m_breadcrumbs = array();
	protected $m_generatedBreadcrumb = '';

	public function setBreadcrumbData(&$data)
	{
		if ($data)
			$this->m_breadcrumbs = $data;

		return $this;
	}

	public function getCrumb()
	{
		return $this->m_generatedBreadcrumb;
	}

	public function buildBreadcrumb($type = 'li', $attrActive = 'class="last"', $attrPassive = '', $linkRel = 'np', $rawOutput = false)
	{
		if (!$this->m_breadcrumbs)
			return !$rawOutput ? '' : $this;

		$crumb = '';
		$this->m_generatedBreadcrumb = '';

		$levels = sizeof($this->m_breadcrumbs);
		if (!$levels)
			return !$rawOutput ? '' : $this;

		$bc = &$this->m_breadcrumbs;
		$core_url = $this->getWowUrl();

		for ($i = 0; $i < $levels; ++$i)
		{
			if (!isset($bc[$i]['link']) || (!isset($bc[$i]['caption']) && !isset($bc[$i]['locale_index'])))
				continue;

			if ($i == ($levels - 1))
				$crumb .= '<' . $type . ($attrActive ? ' ' . $attrActive : '') . '>' . NL;
			else
				$crumb .= '<' . $type . ($attrPassive ? ' ' . $attrPassive : '') . '>' . NL;

			$crumb .= '<a href="' . $core_url . $bc[$i]['link'] . '"' . ($linkRel ? ' rel="' . $linkRel . '"' : '') . '>';

			if (isset($bc[$i]['caption']) && $bc[$i]['caption'])
				$crumb .= $bc[$i]['caption'];
			elseif (isset($bc[$i]['locale_index']) && $bc[$i]['locale_index'])
				$crumb .= $this->c('Locale')->getString($bc[$i]['locale_index']);

			$crumb .= NL . '</a>' . NL;
			$crumb .= '</' . $type . '>' . NL;
		}

		$this->m_generatedBreadcrumb = $crumb;

		if ($rawOutput)
		{
			echo $crumb;
			return $this;
		}

		return $crumb;
	}
}