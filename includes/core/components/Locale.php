<?php

/**
 * Copyright (C) 2009-2011 Shadez <https://github.com/Shadez>
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

class Locale_Component extends Component
{
	protected $m_localeHolder = null;
	protected $m_localeName = '';
	protected $m_localeID = -1;

	public function initialize()
	{
		$locale = null;
		if (!$this->c('Cookie')->read('locale'))
		{
			$locale = $this->c('Config')->getValue('site.locale.default');
			$this->c('Cookie')->write('locale', $locale);
		}
		else
			$locale = $this->c('Cookie')->read('locale');

		return $this->setLocale($locale);
	}

	private function clearLocaleName(&$locale_name)
	{
		$locale_name = strtolower(trim(str_replace(array('-', '_', '.', ' '), null, $locale_name)));
	}

	protected function getAppropriateLocaleNameForLocale($locale_name)
	{
		$this->clearLocaleName($locale_name);

		switch($locale_name)
		{
			case 'de':
			case 'dede':
				return 'de';
			case 'en':
			case 'engb':
			case 'enus':
				return 'en';
			case 'es':
			case 'eses':
			case 'esmx':
				return 'es';
			case 'fr':
			case 'frfr':
				return 'fr';
			case 'ru':
			case 'ruru':
				return 'ru';
		}
	}

	protected function loadLocale()
	{
		if ($this->m_localeName == null || $this->m_localeID == -1)
		{
			$this->c('Log')->writeError('%s : unable to load locale - locale name or locale ID is empty!', __METHOD__);
			return $this;
		}

		$siteLocale = array();
		$this->getLocaleFile(SITE_LOCALES_DIR, $siteLocale);

		$this->m_localeHolder = $siteLocale;

		return $this;
	}

	private function getLocaleFile($path, &$localeHolder)
	{
		$Core_Locale = array();
		$Site_Locale = array();

		if (file_exists($path . 'locale_' . $this->m_localeName . '.php'))
		{
			include($path . 'locale_' . $this->m_localeName . '.php');
			$localeHolder = $Site_Locale;
		}
		elseif(file_exists($path . 'locale_' . $this->c('Config')->getValue('site.locale.default') . '.php'))
		{
			include($path . 'locale_' . $this->c('Config')->getValue('site.locale.default') . '.php');
			$localeHolder = $Site_Locale;
		}
		else
			$this->core->terminate('Site locale (' . strtoupper($this->m_localeName) . ') was not found');

		unset($Site_Locale);

		return $this;
	}

	public function setLocale($locale_name, $locale_id = -1, $load_locale = true)
	{
		$this->m_localeName = $this->getAppropriateLocaleNameForLocale($locale_name);

		if ($locale_id == -1)
			$this->m_localeID = $this->GetLocaleIDForLocale($this->m_localeName);
		else
			$this->m_localeID = $locale_id;

		if (!defined('LOCALE'))
			define('LOCALE', $this->m_localeName);

		if ($load_locale)
			$this->loadLocale();

		$this->c('Cookie')->write('locale', $this->m_localeName);

		return $this;
	}

	public function GetLocaleIDForLocale($locale)
	{
		$this->clearLocaleName($locale);

		switch($locale)
		{
			case 'de':
			case 'dede':
				return LOCALE_DE;
			case 'en':
			case 'engb':
			case 'enus':
				return LOCALE_EN;
			case 'es':
			case 'eses':
			case 'esmx':
				return LOCALE_ES;
			case 'fr':
			case 'frfr':
				return LOCALE_FR;
			case 'ru':
			case 'ruru':
				return LOCALE_RU;
		}
	}

	public function isLocale($locale, $id)
	{
		$this->clearLocaleName($locale);

		switch($locale)
		{
			case 'de':
			case 'dede':
				return $id == LOCALE_DE;
			case 'en':
			case 'engb':
			case 'enus':
				return $id == LOCALE_EN;
			case 'es':
			case 'eses':
			case 'esmx':
				return $id == LOCALE_ES;
			case 'fr':
			case 'frfr':
				return $id == LOCALE_FR;
			case 'ru':
			case 'ruru':
				return $id == LOCALE_RU;
		}

		return false;
	}

	public function GetLocale($type = LOCALE_SINGLE)
	{
		switch($type)
		{
			case LOCALE_DOUBLE:
				return $this->m_localeID == LOCALE_EN ? 'en-gb' : $this->m_localeName . '-' . $this->m_localeName;
			case LOCALE_SPLIT:
				return $this->m_localeID == LOCALE_EN ? 'engb' : $this->m_localeName . $this->m_localeName;
			case LOCALE_PATH:
				return $this->m_localeID == LOCALE_EN ? 'en_gb' : $this->m_localeName . '_' . $this->m_localeName;
			case LOCALE_SINGLE:
			default:
				return $this->m_localeName;
		}
	}

	public function GetLocaleID()
	{
		return $this->m_localeID;
	}

	public function getString($index, $gender = -1)
	{
		if (!isset($this->m_localeHolder[$index]))
			return $index;

		$string = $this->m_localeHolder[$index];

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

        return $string;
	}

	public function format($index)
	{
		$args = func_get_args();
		$args[0] = $this->getString($index);

		return call_user_func_array('sprintf', $args);
	}

	public function extraFormat($index, $replacements)
	{
		$str = $this->getString($index);
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
}