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

$SiteConfigs = array(
	'app' => array(
		'disable_ie' => true,
		'path' => '',
		'cdn_path' => '',
		'layout' => array(
			'title' => 'SF Demo',
			'title_delimiter' => ' :: ',
		),
	),
	'core' => array(
		'url_string' => 'mOUjX93'
	),
	'controller' => array(
		'home_only' => false,
		'default_layout' => array('layout', 'default.layout'),
		'groups' => array()
	),
	'events' => array(
		'disabled' => false,
	),
	'i18n' => array(
		'default' => 'ru',
		'available' => array(
			'ru', 'en'
		),
		'localeIndex' => array(
			0, 1
		),
		'disableLocaleIndex' => false,
	),
	'logging' => array(
		'enabled' => true,
		'level' => 3,
	)
);