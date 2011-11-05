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

class Models_Tool_Component extends Component
{
	protected $m_step = 0;
	protected $m_data = array();
	protected $m_dbType = '';
	protected $m_tables = array();
	protected $m_models = array();

	public function setStep($step)
	{
		$this->m_step = intval($step);

		switch ($this->m_step)
		{
			case 1:
				$dbType = isset($_GET['dbtype']) ? $_GET['dbtype'] : '';

				if (!$dbType)
					return $this;

				if (!$this->c('Db')->isDatabaseAvailable($dbType))
					return $this;

				$this->findTables($dbType);
				break;
			case 2:
				$dbType = isset($_GET['dbtype']) ? $_GET['dbtype'] : '';

				if (!$dbType)
					return $this;

				if (!$this->c('Db')->isDatabaseAvailable($dbType))
					return $this;

				$this->findTables($dbType);

				if ($this->m_tables)
				{
					if ($_GET['table'] == '-1')
					{
						foreach ($this->m_tables as $t)
							$this->generateModel($t);
					}
					elseif (in_array($_GET['table'], $this->m_tables))
					{
						$this->generateModel($_GET['table']);
					}
				}
				break;
		}
		return $this;
	}

	protected function generateModel($table)
	{
		if (!$this->m_dbType)
			return $this;

		$fields = $this->c('Db')->getDb($this->m_dbType)->select("SHOW COLUMNS FROM " . $this->c('Config')->getValue('database.' . $this->m_dbType . '.db_name') . '.' . $table);

		if (!$fields)
			return $this;

		$this->parseFields($fields, $table);

		return $this;
	}

	protected function parseFields(&$fields, $table)
	{
		$text = '<?php

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
 **/' . NL . NL;

		$n = explode('_', $table);
		$text .= 'class ';
		$model = '';
		if ($n)
		{
			foreach ($n as $t)
				$model .= ucfirst($t);
		}
		else $model = ucfirst($table);

		$text .= $model . '_Model_Component extends Model_Db_Component' . NL . '{' . NLTAB;
		$text .= 'public $m_model = \'' . $model . '\';' . NLTAB;
		$text .= 'public $m_table = \'' . $table . '\';' . NLTAB;
		$text .= 'public $m_dbType = \'' . $this->m_dbType . '\';' . NLTAB;
		$text .= 'public $m_fields = array(' . NLTAB;
		$locale_fields = array();
		foreach ($fields as $field)
		{
			if (!isset($field['Field']) || in_array($field['Field'], $locale_fields))
				continue;

			if ($field['Field'] == 'id')
				$text .= TAB . '\'' . $field['Field'] . '\' => \'Id\',' . NLTAB;
			else
				if (preg_match('/text/', $field['Type']) || preg_match('/varchar/', $field['Type']))
					$text .= TAB . '\'' . $field['Field'] . '\' => array(\'type\' => \'string\'),' . NLTAB;
				elseif (preg_match('/float/', $field['Type']) || preg_match('/double/', $field['Type']))
					$text .= TAB . '\'' . $field['Field'] . '\' => array(\'type\' => \'float\'),' . NLTAB;
				else
					$text .= TAB . '\'' . $field['Field'] . '\' => array(\'type\' => \'integer\'),' . NLTAB;
		}
		$text .= ');' . NL . '}';
		$fName = SITE_DIR . 'components' . DS . 'models' . DS . ucfirst(strtolower($model)) . '.php';
		if (file_exists($fName)) 
		{
			echo '<b>Warning</b>: overwriting file ' . $fName . '!<br />';
		}
		file_put_contents($fName, $text);
	}

	protected function findTables($type)
	{
		$this->m_dbType = $type;

		$db = $this->c('Config')->getValue('database.' . $type);

		if (!$db)
			return $this;

		$tables = $this->c('Db')->getDb($type)->select("SHOW TABLES FROM " . $db['db_name']);

		if ($tables)
		{
			foreach ($tables as $t)
				$this->m_tables[] = $t['Tables_in_' . $db['db_name']];
		}

		return $this;
	}

	public function getTables()
	{
		return $this->m_tables;
	}

	public function setData($key, $val)
	{
		$this->m_data[$key] = $val;

		return $this;
	}

	public function getData($key)
	{
		return isset($this->m_data[$key]) ? $this->m_data[$key] : false;
	}

	public function getDatabases()
	{
		return array_keys($this->c('Config')->getValue('database'));
	}
}