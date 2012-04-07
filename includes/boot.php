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

if (!defined('BOOT_FILE'))
	exit;

session_start();
error_reporting(E_ALL);

if (!isset($debug))
	$debug = true;

$tstart = array_sum(explode(' ', microtime()));

define('ROOT', dirname(dirname(__FILE__)));
define('DS', DIRECTORY_SEPARATOR);

if (!defined('WEBROOT_DIR'))
	define('WEBROOT_DIR', ROOT . DS . 'webroot' . DS);

define('INCLUDES_DIR', ROOT . DS . 'includes' . DS);

define('CORE_DIR', INCLUDES_DIR . 'core' . DS);
define('CORE_CLASSES_DIR', CORE_DIR . 'classes' . DS);
define('CORE_COMPONENTS_DIR', CORE_DIR . 'components' . DS);
define('CORE_MODELS_DIR', CORE_COMPONENTS_DIR . 'models' . DS);

if (!defined('SITE_DIR'))
	define('SITE_DIR', INCLUDES_DIR . 'site' . DS);

if (!defined('TEMPLATES_DIR'))
	define('TEMPLATES_DIR', INCLUDES_DIR . 'templates' . DS);

define('SITE_CLASSES_DIR', SITE_DIR . 'classes' . DS);
define('SITE_CONFIGS_DIR', SITE_DIR . 'configs' . DS);
define('SITE_COMPONENTS_DIR', SITE_DIR . 'components' . DS);
define('SITE_LOCALES_DIR', SITE_DIR . 'locales' . DS);
define('SITE_TEMPLATES_DIR', SITE_DIR . 'templates' . DS);
define('SITE_LAYOUTS_DIR', SITE_DIR . 'layouts' . DS);

if (!defined('TEMPLATE_EXT'))
	define('TEMPLATE_EXT', 'ctp');

define('NL', "\n");
define('CR', "\r");
define('TAB', "\t");
define('NLTAB', NL . TAB);
define('TABNL', TAB . NL);
define('CRNL', CR . NL);
define('NLCR', NL . CR);

// Load required files
require_once(INCLUDES_DIR . 'AppCrash.php');
require_once(CORE_DIR . 'dumpvar.php');
require_once(CORE_CLASSES_DIR . 'Autoload.php');
require_once(CORE_CLASSES_DIR . 'Component.php');
require_once(CORE_DIR . 'CoreDefines.php');
require_once(SITE_DIR . 'SiteDefines.php');

if (file_exists(SITE_DIR . 'SiteLoader.php'))
	require_once(SITE_DIR . 'SiteLoader.php');

// IE?
define('IE_BROWSER', preg_match('/MSIE/six', $_SERVER['HTTP_USER_AGENT']));

// Register classes autoloader
Autoload::register();

// Run application
try
{
	// Create core
	$core = Core_Component::create();

	// Execute all actions
	$core->execute();

	// Collect MySQL statistics for all DBs
	$db_types = array_keys($core->c('Config')->getValue('database'));
	$totalStat = array('count' => 0, 'time' => 0.0);

	if ($debug)
	{
		foreach ($db_types as $type)
		{
			$mysql_statistics[$type] = $core->c('Db')->getStatistics($type);

			if (!$mysql_statistics[$type])
			{
				unset($mysql_statistics[$type]);
				continue;
			}

			$totalStat['count'] += $mysql_statistics[$type]['queryCount'];
			$totalStat['time'] += $mysql_statistics[$type]['queryGenerationTime'];
		}

		$objects_count = $core->getCreatedComponentsObjectsCount();
		$components_stacktrace = Autoload::getComponentsStackTrace();
	}

	if (!defined('SKIP_SHUTDOWN'))
		$core->shutdown(); // Shutdown application
}
catch(Exception $e)
{
	$appCrash = new AppCrash($e);

	if ($debug)
		require_once(INCLUDES_DIR . 'ExceptionPage.php');
	else
		require_once(INCLUDES_DIR . 'ExceptionPageProduction.php');

	exit(1);
}

if (!function_exists('outputDebugInfo'))
{
	/**
	 * Outputs debug info.
	 * You can implement this function in index.php if you want to change debug outputing
	 * (if you're using XML, for example, you shouldn't use raw HTML)
	 *
	 * @param  float $tstart
	 * @param  array $mysql_statistics
	 * @param  array $totalStat
	 * @return void
	 **/
	function outputDebugInfo($tstart, $mysql_statistics, $totalStat, $classes, $objects_count, $components_stacktrace)
	{
		echo '<style type="text/css">
			.debug {padding:5px; border:1px solid #000000;}
			#toggleDebugInfo {cursor:pointer;}
			.n {border-top: none;}
		</style>
		<script language="javascript">
		function toggleDebug() {
			var el = document.getElementById("debug");
			el.style.display = (el.style.display == "none") ? "" : "none";
			if (el.style.display == "none")
				document.getElementById("toggleDebugInfo").innerHTML = "Show debug info";
			else
				document.getElementById("toggleDebugInfo").innerHTML = "Hide debug info";
		}
		</script>
		<div class="debug" id="toggleDebugInfo" onclick="toggleDebug();">Show debug info</div>
		';
		echo NL . NL . '<div class="debug n" id="debug" style="display:none">' . NL;
		$totaltime = sprintf('%.2f', (array_sum(explode(' ', microtime())) - $tstart));
		printf('Page generated in ~%.2f sec.<br />Memory usage: ~%.2f mbytes.<br />Memory usage peak: ~%.2f mbytes.<br />', $totaltime, ((memory_get_usage(true)/1048576 * 100000)/100000) , ((memory_get_peak_usage(true) / 1048576 * 100000)/100000));
		if ($totaltime > 1)
			echo '<h1 style="color:#ff0000;">WARNING: seems that your page generation time is too large, please, contact with developer</h1>';

		if (memory_get_usage(true) > 7500000)
			echo '<h1 style="color:#ff0000;">WARNING: seems that your page takes a lot of memory, please, contact with developer</h1>';

		if ($mysql_statistics)
		{
			foreach ($mysql_statistics as $type => $stat)
				printf('MySQL queries count for %s DB: %d, approx. time: %.2f ms.<br />', $type, $stat['queryCount'], $stat['queryGenerationTime']);
		}

		printf('Total MySQL queries count: %d, total approx. time: %.2f ms.<br />', $totalStat['count'], $totalStat['time']);

		echo '<br />Loaded components:<br /><div id="loadedComponents"><small><ul><li>';
		echo implode('<li>', array_keys($classes));
		echo '</ul></small>Total loaded classes count: ' . sizeof($classes) . '<br />Total objects created: ' . $objects_count . '</div><br />Components Stack Trace:<br />';
		echo implode('<br />', $components_stacktrace);
		echo NL . '</div>';
	}
}

if ($debug && !defined('AJAX_PAGE'))
{
	outputDebugInfo($tstart, $mysql_statistics, $totalStat, Autoload::getLoadedComponents(), $objects_count, $components_stacktrace);
}