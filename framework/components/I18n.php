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

class I18n_Component extends Component
{
	protected $m_strings = array();
	protected $m_formats = array();
	protected $m_localeName = '';
	protected $m_namePieces = array();
	protected $m_localeId = -1;
	protected $m_i18nName = '';

	public function initialize()
	{
		$locale = '';

		if (!$this->c('Cookie')->read('locale'))
		{
			$locale = $this->c('Config')->getValue('i18n.default');
			$this->c('Cookie')->write('locale', $locale);
		}
		else
			$locale = $this->c('Cookie')->read('locale');

		return $this->setLocale($locale);
	}

	public function setLocale($locale_name)
	{
		if (!$locale_name)
			throw new I18nCrash_Exception_Component('no locale name provided');
	
		$this->m_localeName = $locale_name;

		$this->c('Cookie')->write('locale', $this->m_localeName);

		return $this->loadLocale();
	}

	protected function loadLocale()
	{
		if (!$this->m_localeName)
			throw new I18nCrash_Exception_Component('locale name was not found');

		$stringsFile = APP_I18N_DIR . $this->m_localeName . DS . 'strings_' . $this->m_localeName . '.' . PHP_EXT;
		$formatsFile = APP_I18N_DIR . $this->m_localeName . DS . 'formats_' . $this->m_localeName . '.' . PHP_EXT;

		if (!file_exists($stringsFile) || !file_exists($formatsFile))
			throw new I18nCrash_Exception_Component('locale ' . $this->m_localeName . ' was not found');

		$App_Strings = array();
		$App_Formats = array();

		require($stringsFile);
		require($formatsFile);

		if (!$App_Strings || !$App_Formats || !isset($App_Strings['locale']) || !$App_Strings['locale'])
			throw new I18nCrash_Exception_Component('corrupted locale ' . $this->m_localeName . ' was found');

		$this->m_strings = $App_Strings;
		$this->m_formats = $App_Formats;

		$this->m_namePieces = $this->m_strings['locale']['pieces'];
		$this->m_localeId = $this->m_strings['locale']['id'];
		$this->m_i18nName = $this->m_strings['locale']['name'];

		return $this;
	}

	public function getLocale($type = LOCALE_SINGLE)
	{
		switch($type)
		{
			case LOCALE_DOUBLE:
				return $this->m_namePieces[0] . '-' . $this->m_namePieces[1];
			case LOCALE_SPLIT:
				return $this->m_namePieces[0] . $this->m_namePieces[1];
			case LOCALE_PATH:
				return $this->m_namePieces[0] . '_' . $this->m_namePieces[1];
			case LOCALE_SINGLE:
			default:
				return $this->m_namePieces[0];
		}
	}

	public function getLocaleId()
	{
		return $this->m_localeId;
	}

	private function findString($indexes, $holder)
	{
		$idx = trim(array_shift($indexes));

		if (isset($holder[$idx]))
			$holder = $holder[$idx];
		else
			return -1;

		if (is_string($holder))
			return $holder;

		return $this->findString($indexes, $holder);
	}

	public function getString($index, $gender = -1)
	{
		$string = $this->findString(explode('.', $index), $this->m_strings);

		if ($string === -1)
			return $index;

		if (is_string($string))
		{
			// Replace $gTEXT_MALE:TEXT_FEMALE; to correct one according with provided gender ID.
			// AoWoW
			$matches = array();

			if(preg_match('/\$g(.*?):(.*?);/iu', $string, $matches))
			{
				if(!is_array($matches) || !isset($matches[0]) || !isset($matches[1]) || !isset($matches[2]))
					return $string;

				switch($gender)
				{
					case GENDER_FEMALE:
						$string = str_replace($matches[0], $matches[2], $string);
						break;
					case GENDER_MALE:
					default:
						$string = str_replace($matches[0], $matches[1], $string);
						break;
				}
			}
		}

        return $string;
	}

	public function format($index)
	{
		$args = func_get_args();
		$args[0] = $this->getString($index);

		return call_user_func_array('sprintf', $args);
	}

	public function extraFormat($index, $replacements, $gender = -1)
	{
		$str = $this->getString($index, $gender);

		foreach ($replacements as $type => $value)
		{
			$gender = -1;

			if (is_array($value) && isset($value['gender']))
			{
				$value = $value['index'];
				$gender = $value['gender'];
			}

			if ($this->getString($value, $gender) == $value)
				$str = str_replace('{' . $type . '}', $value, $str);
			else
				$str = str_replace('{' . $type . '}', $this->getString($value, $gender), $str);
		}

		return $str;
	}

	public function dateTimeFormat($type, $subtype, $timestamp = 0)
	{
		$timestamp = $timestamp ? $timestamp : time();

		return isset($this->m_formats[$type][$subtype]) ? date($this->m_formats[$type][$subtype], $timestamp) : date('d.m.Y', $timestamp);
	}

	public function getFormat($index)
	{
		$string = $this->findString(explode('.', $index), $this->m_formats);

		return $string === -1 ? $index : $string;
	}
};