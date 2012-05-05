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

		$file = APP_CONFIGS_DIR . 'Site.php';

		if (file_exists($file))
		{
			require_once($file);

			$this->m_holder = $SiteConfigs;
		}
		else
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

		foreach ($pieces as $piece)
			$holder_path .= '[\'' . $piece . '\']';

		return $holder_path;
	}

	private function findValue($indexes, $holder, $address = false)
	{
		$idx = trim(array_shift($indexes));

		if (isset($holder[$idx]))
			$holder = $holder[$idx];
		else
			return false;

		if (!$indexes)
			return $holder;

		return $this->findValue($indexes, $holder);
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

		return $this->findValue(explode('.', $path), $this->m_holder);
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
			return $this;

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

';
		$str .= '$SiteConfigs = ' . var_export($this->m_holder, true) . ';';
		file_put_contents(APP_CONFIGS_DIR . 'Site.php', $str);
	}
};