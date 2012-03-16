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

class Image_Component extends Component
{
	private $m_image = null;
	private $m_imgType = 0;
	private $m_isThumb = false;

	public function initialize()
	{
		$imgId = $this->core->getUrlAction(1);
		$this->m_isThumb = in_array($this->core->getUrlAction(2), array('thumb', 't'));

		if (!$imgId)
			return $this;

		$img = $this->i('QueryResult', 'Db')
			->model('Images')
			->fieldCondition('slug', ' =\'' . $imgId . '\'')
			->loadItem();

		if (!$img)
			return $this;

		if ($this->m_isThumb && $img->getRowField('resized') == 1)
			$name = WEBROOT_DIR . 'uploads' . DS . $img->getRowField('author') . DS . 'thumb_' . $img->getRowField('filename');
		else
			$name = WEBROOT_DIR . 'uploads' . DS . $img->getRowField('author') . DS . $img->getRowField('filename');

		switch ($img->getRowField('image_type'))
		{
			case IMGTYPE_JPEG:
				$this->m_image = imagecreatefromjpeg($name);
				break;
			case IMGTYPE_PNG:
				$this->m_image = imagecreatefrompng($name);
				break;
			case IMGTYPE_GIF:
				$this->m_image = imagecreatefromgif($name);
				break;
			default:
				return $this;
		}

		$this->m_imgType = $img->getRowField('image_type');

		$img->free();

		return $this;
	}

	public function isImage()
	{
		return $this->m_image ? true : false;
	}

	public function getImage()
	{
		return $this->m_image;
	}

	public function getImageType()
	{
		return $this->m_imgType;
	}
}