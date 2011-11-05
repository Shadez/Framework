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

class Cache_Component extends Component
{
	protected $m_cacheTypes = array();
	protected $m_cacheLifeTime = 86400;

	public function initialize()
	{
		$dir_name = CACHE_DIR;
		if (!is_dir($dir_name))
			return $this;
			
		$dir = opendir($dir_name);

		while ($file = readdir($dir))
		{
			if (in_array($file, array('.', '..')))
				continue;

			if (is_dir($file) . DS)
				$this->m_cacheTypes[$file] = $dir_name . $file . DS;
		}

		return $this;
	}

	public function getCache($filename, $type = 'default')
	{
		if (!isset($this->m_cacheTypes[$type]))
			return false;

		$file_hash = md5($filename);
		$cache_info = $this->m_cacheTypes[$type] . $file_hash . '.dat';
		$cache_data = $this->m_cacheTypes[$type] . $file_hash . '.cache';
		if (!file_exists($cache_info) || !file_exists($cache_data))
			return false;

		$info = unserialize(file_get_contents($cache_info));
		if ($info->timestamp < time())
		{
			$this->dropCache($this->m_cacheTypes[$type] . $file_hash);
			return false;
		}

		return unserialize(file_get_contents($cache_data));
	}

	public function writeCache($type, $name, &$contents)
	{
		if (!$contents)
			return false;

		if (!isset($this->m_cacheTypes[$type]))
			return false;

		$hash = md5($name);

		$cacheInfo = new CacheInfo;
		$cacheInfo->timestamp = time() + $this->m_cacheLifeTime;
		$cacheInfo->filename = md5($name);
		$cacheInfo->type = $type;
		$cacheInfo->save_stamp = time();

		file_put_contents($this->m_cacheTypes[$type] . $cacheInfo->filename . '.dat', serialize($cacheInfo));
		file_put_contents($this->m_cacheTypes[$type] . $cacheInfo->filename . '.cache', serialize($contents));
		
		unset($cacheInfo);

		return true;
	}

	protected function dropCache($file)
	{
		$cache_info = $file . '.dat';
		$cache_data = $file . '.cache';

		if (file_exists($cache_info))
			unlink($cache_info);

		if (file_exists($cache_data))
			unlink($cache_data);

		return true;
	}
}