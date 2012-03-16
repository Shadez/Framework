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

class Uploader_Component extends Component
{
	const THUMB_W = 400;
	const THUMB_H = 200;

	private $m_file = array();
	private $m_uploadDir = '';
	private $m_userId = 1;
	private $m_status = 0;
	private $m_image = null;
	private $m_slug = '';
	private $m_imgType = 0;

	public function initialize()
	{
		$this->m_file = isset($_FILES['file']) ? $_FILES['file'] : array();

		$this->m_userId = 1;

		$this->m_uploadDir = WEBROOT_DIR . 'uploads' . DS . $this->m_userId . DS;

		if (!is_dir($this->m_uploadDir))
			mkdir($this->m_uploadDir);

		return $this;
	}

	public function getStatus()
	{
		return $this->m_status;
	}

	public function upload()
	{
		if (!$this->m_file)
			return $this;

		$this->moveFile()
			->resizeFileIfRequired()
			->saveFile();

		return $this;
	}

	public function getFileName()
	{
		return $this->m_file['name'];
	}

	public function getFilePath()
	{
		return $this->m_uploadDir . $this->m_file['name'];
	}

	public function getFileType()
	{
		return $this->m_file['type'];
	}

	public function getFileTmpName()
	{
		return $this->m_file['tmp_name'];
	}

	public function getFileExtension()
	{
		switch ($this->m_file['type'])
		{
			case 'image/png':
				return 'png';
			case 'image/gif':
				return 'gif';
			case 'image/jpeg':
			default:
				return 'jpg';
		}
	}

	private function handleDublicateFile()
	{
		$this->m_file['name'] = substr(md5(microtime()), 0, 5) . '_' . $this->m_file['name'];

		return $this;
	}

	private function moveFile()
	{
		if (file_exists($this->getFilePath()))
			$this->handleDublicateFile();

		if (!move_uploaded_file($this->getFileTmpName(), $this->getFilePath()))
			return $this;

		switch ($this->getFileExtension())
		{
			case 'png':
				$this->m_image = imagecreatefrompng($this->getFilePath());
				$this->m_imgType = IMGTYPE_PNG;
				break;
			case 'gif':
				$this->m_image = imagecreatefromgif($this->getFilePath());
				$this->m_imgType = IMGTYPE_GIF;
				break;
			case 'jpg':
			default:
				$this->m_image = imagecreatefromjpeg($this->getFilePath());
				$this->m_imgType = IMGTYPE_JPEG;
				break;
		}

		if (!$this->m_image)
			return $this;

		$this->m_status = 1;

		return $this;
	}

	private function resizeFileIfRequired()
	{
		if ($this->m_status == 0)
			return $this;

		$width = imagesx($this->m_image);
		$height = imagesy($this->m_image);

		if ($width < 1 || $height < 1)
			return $this;

		$ratio1 = $width / self::THUMB_W;
		$ratio2 = $height / self::THUMB_H;

		$new_w = 0;
		$new_h = 0;

		if ($ratio1 > $ratio2)
		{
			$new_w = self::THUMB_W;
			$new_h = $height / $ratio1;
		}
		else
		{
			$new_h = self::THUMB_H;
			$new_w = $width / $ratio2;
		}

		$previewImg = imagecreatetruecolor($new_w, $new_h);
		$palsize = imagecolorstotal($this->m_image);

		for ($i = 0; $i < $palsize; $i++)
		{
			$colors = imagecolorsforindex($img, $i);
			imagecolorallocate($previewImg, $colors['red'], $colors['green'], $colors['blue']);
		}

		imagecopyresampled($previewImg, $this->m_image, 0, 0, 0, 0, $new_w, $new_h, $width, $height);

		$dest = $this->m_uploadDir . 'thumb_' . $this->getFileName();

		switch ($this->getFileExtension())
		{
			case 'jpg':
				imagejpeg($previewImg, $dest, 100);
				break;
			case 'png':
				imagesavealpha($previewImg, true);
				imagepng($previewImg, $dest, 9);
				break;
			case 'gif':
				imagegif($previewImg, $dest);
				break;
			default:
				return $this;
		}

		$this->m_status += 1;
		
		return $this;
	}

	private function saveFile()
	{
		if ($this->m_status == 0)
			return $this;

		$edt = $this->i('Editing')
			->setModel('Images')
			->setType('insert');

		$edt->author = intval($this->m_userId);
		$edt->filename = $this->getFileName();
		$edt->slug = $this->generateSlug();
		$edt->upload_date = time();
		$edt->album_id = 1;
		$edt->private = 0;
		$edt->views = 0;
		$edt->disabled = 0;
		$edt->image_type = $this->m_imgType;

		if ($this->m_status == 2)
			$edt->resized = 1;
		else
			$edt->resized = 0;

		$edt->save();

		$this->m_status += 1;

		return $this;
	}

	private function generateSlug()
	{
		$slug = substr(md5(rand() . microtime()), 0, 6);

		$this->m_slug = $slug;

		return $slug;
	}

	public function getSlug()
	{
		return $this->m_slug;
	}
}