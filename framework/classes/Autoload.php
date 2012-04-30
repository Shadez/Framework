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

	public static function loadClass($className)
	{
		$pieces = explode('_', $className);

		if (strcmp(strtolower($pieces[sizeof($pieces)-1]), 'component') !== false)
			unset($pieces[sizeof($pieces)-1]);

		$size = sizeof($pieces);
		$isController = false;

		if ($size >= 2)
			$isController = strtolower($pieces[$size-1]) == 'controller';

		$paths = array(
			APP_COMPONENTS_DIR, FW_COMPONENTS_DIR
		);

		$file_path = '';

		for ($i = $size - 1; $i >= 0; --$i)
		{
			$file_path .= ($i < $size-1 ? DS : '') . ($i == 0 ? ucfirst(strtolower($pieces[$i])) . '.' . PHP_EXT : strtolower($pieces[$i]));
		}

		$usePath = '';
		$throwExc = false;

		foreach ($paths as $path)
		{
			if (file_exists($path . $file_path))
				$usePath = $path . $file_path;
		}

		if (!$usePath)
			$throwExc = true;
		else
		{
			require_once($usePath);

			if (!class_exists($className))
				$throwExc = true;
		}

		if ($isController && !class_exists($className))
		{
			$className = preg_replace('/[^ \/_A-Za-z-]/', '', $className);
			$className = str_replace(' ', '', $className);

			if (strcmp(strtolower($className), 'default_controller_component'))
				eval('class ' . $className . ' extends Default_Controller_Component {};');
		}
		elseif ($throwExc)
			throw new Exception('class ' . $className . ' was not found');

		self::$m_classes[$className] = true;
	}

	public static function getLoadedClasses()
	{
		return self::$m_classes;
	}
};