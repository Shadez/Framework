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

class Upload_Controller_Component extends Controller_Component
{
	protected function actionIndex($core)
	{
		$this->ajaxPage(true);

		$this->c('Uploader')->upload();

		$json = '';

		if ($this->c('Uploader')->getStatus() == 0)
			$json = '{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "Unable to upload file!"}, "id" : "id"}';
		else
			$json = '{"jsonrpc" : "2.0", "result" : null, "slug": "' . $this->c('Uploader')->getSlug() . '", "resized": ' . ($this->c('Uploader')->getStatus() == 3 ? 1 : 0) . ', "id" : "id"}';

		$core->setVar('content', $json);

		return $this;
	}
}