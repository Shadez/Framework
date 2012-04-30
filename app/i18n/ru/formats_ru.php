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

$App_Formats = array(
    'date' => array(
		'date_numeric' => 'm/d/Y',
		'date_str_month' => 'd F Y',
		'date_str_month_short' => 'd M Y'
	),
	'time' => array(
		'time_full' => 'H:i:s',
		'time_short' => 'H:i'
	),
	'date_time' => array(
		'date_time_numeric_full' => 'm/d/Y H:i:s',
		'date_time_numeric_short' => 'm/d/Y H:i',
		'date_time_str_month_full' => 'd f Y, H:i:s',
		'date_time_str_month_short' => 'd f Y, H:i',
		'date_time_str_month_short_full' => 'd M Y H:i:s',
		'date_time_str_month_short_short' => 'd M Y H:i',
	)
);