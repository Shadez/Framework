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

if (!defined('BOOT_FILE'))
	exit;

/*
 * This is configuration for Router component.
 * It allows to make redirects from pages to pages for specific user groups.
 * $Router's indexes should be an page url string.
 * For example, you want to redirect guests from 'wow/store' page. You can do it by adding this:
 *	'wow/store' => array(
 * 		'destination' => 'newDestination/Url', // will redirect to WOW_ROOT/wow/<locale>/newDestination/Url (because type is 'wow')
 * 		'redirectType' => 'wow',
 * 		'access' => 'guests' // all guests will be redirected
 * 	),
 * 	'wow/admin' => array(
 * 		'destination' => '/', // will redirect to SERVER_ROOT/ (because type is 'app')
 * 		'redirectType' => 'app',
 * 		'access' => 'non-admins' // all non-admins will be redirected
 *	)
 * 'access' can have these values:
 * 		- users (logged in visitors)
 * 		- guests
 * 		- admins (gmlevel >= 1)
 * 		- non-admins
 * 		- only-users (guests and admins will be ignored)
 * 		- everyone
 *
 *	NOTE: you should write all paths _without_ locale ID!
 */

$Router = array(
	/*'/' => array(
		'destination' => '',
		'redirectType' => 'wow',
		'access' => 'everyone'
	),*/
);
?>