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

/**
 * Config component provides easy access to any configuration value
 * @copyright Copyright (C) 2009-2011 Shadez <https://github.com/Shadez>
 * @category  Core
 **/
class Config_Component extends Component
{
	private $m_holder = array();

	public function initialize()
	{
		$this->loadConifgs();

		return $this;
	}

	/**
	 * Loads configuration file into $m_holder
	 * @return Config_Component
	 **/
	public function loadConifgs()
	{
		if ($this->m_holder)
			return $this;

		$file = SITE_CONFIGS_DIR . 'Site.php';

		if (file_exists($file))
		{
			include($file);
			$this->m_holder = $SiteConfig;
		}

		if (!$this->m_holder)
			throw new Config_Exception_Component('Unable to find configuration file!');

		return $this;
	}

	/**
	 * Transforms string path to array accessors
	 * @access private
	 * @param  $path
	 * @return mixed
	 **/
	private function getConfigPath(&$path)
	{
		$holder_path = '';

		$pieces = explode('.', $path);
		if (!$pieces)
			return false;

		foreach ($pieces as &$piece)
			$holder_path .= '[\'' . $piece . '\']';

		return $holder_path;
	}

	/**
	 * Returns configuration value
	 * @param  $path
	 * @return mixed
	 **/
	public function getValue($path)
	{
		if (!$path)
			return false;

		$value = false;

		$holder_path = $this->getConfigPath($path);

		if (!$holder_path)
			return false;

		eval('if (isset($this->m_holder' . $holder_path  .')) $value = $this->m_holder' . $holder_path .';');

		return $value;
	}

	/**
	 * Sets $path value to $value (for current session only)
	 * @param $path
	 * @param $value
	 * @return Config_Component
	 **/
	public function setValue($path, $value)
	{
		if (!$path)
		{
			$this->c('Log')->writeError('%s : unable to set value: path is not defined (value: %s)!', __METHOD__, $value);
			return $this;
		}

		$holder_path = $this->getConfigPath($path);

		if (!$holder_path)
			return $this;

		eval('$this->m_holder' . $holder_path . ' = $value;');

		return $this;
	}

	/**
	 * Returns configuration holder
	 * @return array
	 */
	public function getConfigHolder()
	{
		return $this->m_holder;
	}

	public function updateConfigFile()
	{
		$str = '<?php

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

';
		$str .= '$SiteConfig = ' . var_export($this->m_holder, true) . ';';
		file_put_contents(SITE_CONFIGS_DIR . 'Site.dat', $str);
	}
}