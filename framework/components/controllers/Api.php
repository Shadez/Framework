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

namespace Controllers;
class Api extends \Controller
{
	protected function run()
	{
		$this->ajaxPage();

		// Init site API
		$this->c('SiteApi');

		$resp = array('errno' => -1, 'errmsg' => 'No method provided');

		if (!isset($_GET['apiSig']))
		{
			$resp = array(
				'errno' => -2,
				'ermsg' => 'No API Signature provided'
			);
		}
		elseif (isset($_GET['method']))
			$resp = $this->c('Api')->runApiMethod(addslashes($_GET['method']));

		$this->getCore()->setVar('content', $resp);
	}
}