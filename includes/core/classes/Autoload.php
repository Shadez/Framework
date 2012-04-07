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
 * This class provides support for classes autoloading
 **/
abstract class Autoload
{
	/**
	 * Included classes
	 * @var		array
	 * @static
	 **/
	private static $classes = array();
	private static $stackTrace = array();

	/**
	 * Registers autload handler
	 * @return void
	 * @static
	 **/
	public static function register()
	{
		spl_autoload_register(array(__CLASS__, 'autoloadClass'));
	}

	public static function getLoadedComponents()
	{
		return self::$classes;
	}

	public static function getComponentsStackTrace()
	{
		return self::$stackTrace;
	}

	/**
	 * Autload handler
	 * @param  string $name
	 * @return bool
	 * @static
	 **/
	public static function autoloadClass($name)
	{
		$className = '';
		$classType = '';
		$path = '';
		$folders = array(array('type' => 'site', 'path' => SITE_COMPONENTS_DIR), array('type' => 'core', 'path' => CORE_COMPONENTS_DIR));
		$name = str_replace('-', '', $name);

		$pieces = explode('_', $name);

		$piecesSize = sizeof($pieces);
		$extraFind = false;
		$pathLocked = false;
		if (isset($pieces[$piecesSize-2]) && strtolower($pieces[$piecesSize-2]) == 'controller')
			if (strtolower($pieces[0]) == 'home')
				$extraFind = true;

		if ($pieces)
		{
			$className = $pieces[0];

			if ($extraFind)
			{
				unset($pieces[$piecesSize-1]);
				unset($pieces[$piecesSize-2]);
				unset($pieces[0]);
				$piecesSize = sizeof($pieces);
				$path = 'controllers' . DS . implode(DS, array_reverse($pieces)) . DS . 'Home.php';
				$pathLocked = true;
				$proof = 'Component';
			}
			elseif (isset($pieces[1]) && isset($pieces[2]) && isset($pieces[3]))
			{
				$classType = strtolower($pieces[2]);
				if ($classType != 'db')
					$classType .= 's';

				$proof = $pieces[3];
				$path = $classType . DS . strtolower($pieces[1]) . DS;
			}
			elseif (isset($pieces[1]) && isset($pieces[2]))
			{
				$classType = strtolower($pieces[1]);
				if ($classType != 'db')
					$classType .= 's';
				$proof = $pieces[2];
				$path = $classType . DS;
			}
			elseif (isset($pieces[1]))
				$proof = $pieces[1];
			else
				throw new Exception('Wrong class name: ' . $name . ', unable to continue!');

			if ($proof != 'Component')
				throw new Exception('Wrong class name: ' . $name . ', unable to continue!');

			if (!$pathLocked)
				$path .= ucfirst(strtolower($className)) . '.php';

			$throwException = false;
			$classFound = false;

			foreach ($folders as $folder)
			{
				if (file_exists($folder['path'] . $path))
				{
					self::writeStackTrace('Loading file ' . $path);
					require_once($folder['path'] . $path);
					self::writeStackTrace('File ' . $path . ' was loaded');

					if (!class_exists($name, true))
					{
						self::writeStackTrace('Class ' . $name . ' was not found in ' . $path);
						if ($folder['type'] == 'site')
						{
							// Try to find component from core directory
							if (file_exists($folders[1]['path'] . $path))
							{
								self::writeStackTrace('Loading file ' . $path);
								require_once($folders[1]['path'] . $path);
								if (!class_exists($name, true))
								{
									$throwException = true;
									self::writeStackTrace('Class ' . $name . ' was not found in ' . $folders[1]['path'] . ', exception will be thrown');
								}
							}
						}
					}

					if ($throwException)
						throw new Exception('Class ' . $name . ' was not found!');

					self::$classes[$name] = class_exists($name, true);

					return true;
				}
			}

			$new_class = preg_replace('/[^ \/_A-Za-z-]/', '', $name);
			$new_class = str_replace(' ', '', $new_class);

			if (strtolower($classType) == 'controllers')
				eval('Class ' . $new_class . ' extends Default_Controller_Component {};'); // Create default controller (404 trigger)

			return false;
		}

		return false;
	}

	private static function writeStackTrace($msg)
	{
		self::$stackTrace[] = '<strong>[Autoload]</strong> [' . date('d-m-Y H:i:s') . ']: ' . $msg;
	}
}