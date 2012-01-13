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
	Here you can set what component should be loaded when some condition is satisfied.
	There are 5 condition types: "config", "get", "post", "session" and "cookie".
	When component is being loaded, script checks all conditions listed for this component and returns class filename
	that should be loaded instead of original file.

	Structure of this variable is:
	---
	$Components = array(
		COMPONENT_TYPE *1 => array(
			COMPONENT_NAME => array(
				'conditions' *2 => array(
					CONDITION_TYPE => array(
						CONDITION_KEY => array(
							CONDITION_VALUE_1 => CLASS_FILENAME_1,
							CONDITION_VALUE_2 => CLASS_FILENAME_2,
							...
							CONDITION_VALUE_N => CLASS_FILENAME_N
						)
					)
				)
			)
		)
	);
	---

	--*1: 'Model', 'Db', etc. For default components use 'Default' section.
	--*2: every component must have this section!

	If no condition is satisfied, script will load default component (according to it's name and type).

	Overriden components must have only different filenames, class names must be same as original component's name!
	Example:
		original component  - Data_Model_Component
		original filename   - site/components/models/Data.php

		overriden component - Data_Model_Component
		overriden filename	- site/components/models/Dataoverriden.php
**/

$Components = array(
	'Model' => array(
		'Data' => array(
			'conditions' => array(
				'config' => array(
					'site.locale' => array(
						'en' => 'DataConfigEn',
						'ru' => 'DataConfigRu'
					)
				),
				'get' => array(
					'test_get' => array(
						1 => 'DataGetFirst',
						2 => 'DataGetSecond',
						'loader' => 'DataGetThird'
					)
				),
				'post' => array(
					'test_post' => array(
						1 => 'DataPostFirst',
						2 => 'DataPostSecond',
						'loader' => 'DataPostThird'
					)
				),
				'session' => array(
					'username' => array(
						'admin' => 'DataSessionAdmin'
					)
				),
				'cookie' => array(
					'test_cookie' => array(
						'050105' => 'DataCookieFirst'
					)
				),
			)
		)
	)
);