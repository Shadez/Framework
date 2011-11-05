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

$SiteConfig = array(
	'site' => array(
		'path' => '',
		'log' => array(
			'enabled' => true,
			'level' => 2
		),
		'title' => 'Framework Demo',
		'locale' => array(
			'default' => 'en'
		),
		'locale_indexes' => array(
			0, 1
		)
	),
	'misc' => array(
		'admin_email' => 'admin@' . $_SERVER['SERVER_NAME']
	),
	'session' => array(
		'identifier' => 'fw_sid',
		'user' => array(
			'storage' => 'fw_session'
		),
		'magic_string' => 'SESSION_CONVERT'
	),
	'database' => array(
		'site' => array(
			'host' => 'localhost',
			'user' => 'root',
			'password' => '',
			'db_name' => 'fw_db',
			'charset' => 'UTF8',
			'driver' => 'mysql',
			'prefix' => ''
		)
	)
);