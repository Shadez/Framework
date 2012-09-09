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

namespace Db\Fields;
class Unixtimestamp extends \Db\Field
{
	protected function validateSetter(&$value, $settings)
	{
		if (!(intval($value) == $value))
			$value = strtotime($value);

		return $this;
	}

	protected function validateGetter(&$value, $settings)
	{
		if (intval($value) == $value)
		{
			$format = 'm/d/Y H:i:s';

			if (isset($settings['format']))
				$format = $settings['format'];
			else
				$format = $this->c('I18n')->getFormat('validation.fields.unixtimestamp');

			$value = date($format, intval($value));
		}

		return $this;
	}
};