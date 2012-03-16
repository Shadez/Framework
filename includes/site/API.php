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

$APIMethods = array(
	'core' => array(
		array(
			'method' => 'getRawUrl',
			'request' => 'core.getRawUrl',
			'name' => 'coregetrawurl',
			'desc' => 'Returns raw URL',
			'argc' => 0,
			'argk' => array(),
			'disabled' => false,
			'type' => 'dev'
		),
		array(
			'method' => 'getUrlAction',
			'request' => 'core.getUrlAction',
			'name' => 'coregeturlaction',
			'desc' => 'Returns URL action by index',
			'argc' => 1,
			'argk' => array('idx' => 'int'),
			'disabled' => false,
			'type' => 'dev'
		),
		array(
			'method' => 'getVersion',
			'request' => 'core.getVersion',
			'name' => 'coregetversion',
			'desc' => 'Returns core version',
			'argc' => 2,
			'argk' => array('fullVersion' => 'bool', 'info' => 'bool'),
			'disabled' => false,
			'type' => 'admin'
		),
		array(
			'method' => 'getUsername',
			'request' => 'site.getUsername',
			'name' => 'sitegetusername',
			'desc' => 'Returns active username',
			'argc' => 0,
			'argk' => array(),
			'disabled' => false,
			'type' => 'user'
		),
	),
);