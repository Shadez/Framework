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

$ClientJS = array(
	'_overall' => array(
		'header' => array(
			array(
				'file' => 'http://bp.yahooapis.com/2.4.21/browserplus-min.js',
				'external' => true
			),
			array(
				'file' => '/js/plupload/plupload.js',
			),
			array(
				'file' => '/js/plupload/plupload.html5.js',
			),
			array(
				'file' => '/js/jquery-latest.min.js',
			),
			array(
				'code' => 'jQuery.noConflict();'
			)
		)
	)
);