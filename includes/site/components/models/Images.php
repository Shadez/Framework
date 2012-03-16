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

class Images_Model_Component extends Model_Db_Component
{
	public $m_model = 'Images';
	public $m_table = 'images';
	public $m_dbType = 'site';
	public $m_fields = array(
		'image_id' => array('type' => 'integer'),
		'author' => array('type' => 'integer'),
		'filename' => array('type' => 'string'),
		'slug' => array('type' => 'string'),
		'upload_date' => array('type' => 'integer'),
		'album_id' => array('type' => 'integer'),
		'private' => array('type' => 'integer'),
		'views' => array('type' => 'integer'),
		'disabled' => array('type' => 'integer'),
		'resized' => array('type' => 'integer'),
		'image_type' => array('type' => 'integer'),
	);
}