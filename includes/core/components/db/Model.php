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

class Model_Db_Component extends Component
{
	public $m_model  = '';
	public $m_table  = '';
	public $m_dbType = '';
	public $m_fields = array();
	public $m_types = array();
	public $m_aliases = array();

	private $m_defaultFields = array();
	private $m_defaultAliases = array();

	public function initialize()
	{
		$this->m_types['default'] = array_values($this->m_fields);

		$this->m_defaultFields = $this->m_fields;
		$this->m_defaultAliases = $this->m_aliases;

		return $this;
	}

	/**
	 * Returns the ID field for current model
	 * @return string
	 **/
	protected function getModelIdField()
	{
		foreach ($this->m_fields as $field => $type)
			if (is_string($type) && $type == 'Id')
				return $field;

		return 'id';
	}

	/**
	 * Returns model field names
	 * @return array
	 **/
	public function getFields()
	{
		$data = array();
		$localePattern = '/_' . $this->c('Locale')->GetLocale() . '/';
		$dbLocalePattern = '/_loc' . $this->c('Locale')->GetLocaleID() . '/';

		foreach($this->m_fields as $field => $type)
		{
			if (!is_string($type))
				continue;

			switch($type)
			{
				case 'Locale':
					if (preg_match($localePattern, $field))
						continue;

					$new = $field . '_' . $this->c('Locale')->getLocale();
					$this->m_aliases[$new] = $field;
					$data[] = array($field, $new);
					break;
				case 'DbLocale':
					if (preg_match($dbLocalePattern, $field))
						continue;

					$new = $field . '_loc' . $this->c('Locale')->getLocaleId();
					$this->m_aliases[$new] = $field;
					$data[] = array($field, $new);
					break;
			}
		}

		if ($data)
		{
			foreach ($data as $info)
			{
				$tmp = $this->m_fields[$info[0]];
				$this->m_fields[$info[1]] = $tmp;
				unset($this->m_fields[$info[0]]);
			}
		}

		return $this->m_fields;
	}

	/**
	 * Removes dynamic (or static) field alias by field name
	 * @param  string $fieldName
	 * @return Model_Db_Component
	 **/
	public function removeAliasByFieldName($fieldName)
	{
		if (isset($this->m_aliases[$fieldName]))
			unset($this->m_aliases[$fieldName]);

		return $this;
	}

	/**
	 * Removes dynamic (or static) field alias by alias name
	 * @param  string $aliasName
	 * @return Model_Db_Component
	 **/
	public function removeAliasByAliasName($aliasName)
	{
		if (!$this->m_aliases)
			return $this;

		$fieldToRemove = false;

		foreach ($this->m_aliases as $field => $alias)
		{
			if ($aliasName == $alias)
			{
				$fieldToRemove = $field;
				break;
			}
		}

		if ($fieldToRemove && isset($this->m_aliases[$fieldToRemove]))
			unset($this->m_aliases[$fieldToRemove]);

		return $this;
	}

	/**
	 * Restores original model fields
	 * @return Model_Db_Component
	 **/
	public function restoreFields()
	{
		$this->m_fields = $this->m_defaultFields;

		return $this;
	}

	/**
	 * Restores original model aliases
	 * @return Model_Db_Component
	 **/
	public function restoreAliases()
	{
		$this->m_aliases = $this->m_defaultAliases;

		return $this;
	}
}