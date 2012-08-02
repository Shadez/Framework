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

abstract class Autoload
{
	private static $m_classes = array();

	public static function register()
	{
		spl_autoload_register('Autoload::loadClass');
	}

	public static function loadClass($className, $checkOnly = false)
	{
		// Since we're using namespaces, let's find out what NS we've got now
		$ns = explode('\\', $className);

		$name = $ns[ sizeof($ns) - 1];
		unset($ns[ sizeof($ns) - 1]);
		$ns = array_values($ns); // Rebuild indexes

		$ns_path = implode(DS, array_map(function($v) {
			return strtolower($v);
		}, $ns));

		$default_pathes = array(
			array(
				'dir' => APP_COMPONENTS_DIR,
				'info' => 'App Component',
				'type' => 'component'
			),
			array(
				'dir' => FW_COMPONENTS_DIR,
				'info' => 'Framework Component',
				'type' => 'component'
			),
			array(
				'dir' => APP_INTERFACES_DIR,
				'info' => 'App Interface',
				'type' => 'interface'
			),
			array(
				'dir' => FW_INTERFACES_DIR,
				'info' => 'Framework Interface',
				'type' => 'interface'
			),
		);

		$file = ucfirst(strtolower($name)) . '.php';
		$file_path = '';
		$throw_exception = false;
		$file_path_info = array();

		if (!$ns)
		{
			// No namespace, root component
			foreach ($default_pathes as $fp)
			{
				if (file_exists($fp['dir'] . $file))
				{
					$file_path = $fp['dir'] . $file;
					$file_path_info = $fp;
				}
			}

			if (!$file_path || !$file_path_info)
				$throw_exception = true;
		}
		else
		{
			foreach ($default_pathes as $fp)
			{
				if (file_exists($fp['dir'] . $ns_path . DS . $file))
				{
					$file_path = $fp['dir'] . $ns_path . DS . $file;
					$file_path_info = $fp;
				}
			}

			if (!$file_path || !$file_path_info)
				$throw_exception = true;
		}

		if ($checkOnly)
			return !$throw_exception;

		if ($throw_exception)
			throw new Exception('Class ' . $className . ' was not found!');

		require_once($file_path);

		$file_path_info['path'] = $file_path;
		$file_path_info['class'] = $className;
		unset($file_path_info['dir']);

		self::$m_classes[$className] = $file_path_info;
	}

	public static function getLoadedClasses()
	{
		return self::$m_classes;
	}
};