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

if (!defined('BOOT_FILE'))
	exit(1);

session_start();
error_reporting(E_ALL);

if (!isset($debug))
	$debug = true;

$tstart = array_sum(explode(' ', microtime()));

// Define global constants
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', (dirname(dirname(__FILE__))) . DS);

if (!defined('FW_DIR'))
	define('FW_DIR', dirname(dirname(__FILE__)) . DS . 'framework' . DS);

if (!defined('LIB_DIR'))
	define('LIB_DIR', ROOT . 'lib' . DS);

define('SHARED_DIR', LIB_DIR . 'shared' . DS);

define('FW_CLASSES_DIR', FW_DIR . 'classes' . DS);
define('FW_COMPONENTS_DIR', FW_DIR . 'components' . DS);
define('FW_INTERFACES_DIR', FW_DIR . 'interfaces' . DS);

if (!defined('STATIC_DIR'))
	define('STATIC_DIR', ROOT . 'static' . DS);
if (!defined('APP_DIR'))
	define('APP_DIR', ROOT . 'app' . DS);
if (!defined('TEMPLATES_DIR'))
	define('TEMPLATES_DIR', APP_DIR . 'templates' . DS);
if (!defined('TPL_EXT'))
	define('TPL_EXT', 'tpl');
if (!defined('PHP_EXT'))
	define('PHP_EXT', 'php');

// PHP-Error by JosephLenton <https://github.com/JosephLenton/PHP-Error>
if ($debug)
{
	require_once(SHARED_DIR . 'php-error' . DS . 'php_error.' . PHP_EXT);
	\php_error\reportErrors();
}

define('APP_CLASSES_DIR', APP_DIR . 'classes' . DS);
define('APP_COMPONENTS_DIR', APP_DIR . 'components' . DS);
define('APP_INTERFACES_DIR', APP_DIR . 'interfaces' . DS);
define('APP_CONFIGS_DIR', APP_DIR . 'configs' . DS);
define('APP_I18N_DIR', APP_DIR . 'i18n' .DS);
define('APP_LAYOUTS_DIR', APP_DIR . 'layouts' . DS);

// Load system files
require_once(FW_DIR . 'dumpvar.' . PHP_EXT);
require_once(FW_CLASSES_DIR . 'Autoload.' . PHP_EXT);
require_once(FW_CLASSES_DIR . 'AbstractComponent.' . PHP_EXT);
require_once(FW_DIR . 'FwDefines.' . PHP_EXT);
require_once(APP_DIR . 'AppDefines.' . PHP_EXT);

if (file_exists(APP_DIR . 'AppLoader.' . PHP_EXT))
	require_once(APP_DIR . 'AppLoader.' . PHP_EXT);

// Register classes autoloader
Autoload::register();

define('IE_BROWSER', isset($_SERVER['HTTP_USER_AGENT']) ? preg_match('/MSIE/six', $_SERVER['HTTP_USER_AGENT']) : false);

try
{
	$core = \Core::create()->run();
}
catch (Exception $e)
{
	require_once(FW_DIR . 'AppCrash.' . PHP_EXT);

	$appCrash = new AppCrash($e);

	if ($debug)
		require_once(FW_DIR . 'ExceptionPage.php');
	else
		require_once(FW_DIR . 'ExceptionPageProduction.php');

	exit(1);
}

if ($debug && !$core->getActiveController()->isAjaxPage())
{
	require_once(FW_DIR . 'Debug.' . PHP_EXT);

	$debug = new SFDebug($core);
	$tend = array_sum(explode(' ', microtime()));

	$debug->setData(array(
		'start_time' => $tstart,
		'end_time' => $tend,
		'run_time' => sprintf('%.3f', ($tend - $tstart)),
		'memory_usage' => sprintf('%.2f', ((memory_get_usage(true)/1048576 * 100000)/100000)),
		'memory_usage_peak' => sprintf('%.2f', ((memory_get_peak_usage(true)/1048576 * 100000)/100000)),
		'classes' => Autoload::getLoadedClasses()
	));
}