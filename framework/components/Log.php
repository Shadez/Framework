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

class Log_Component extends Component
{
	/**
	 * Log filename
	 * @access private
	 * @var    string
	 **/
	private $m_logFile = '';

	/**
	 * Log level
	 * @access private
	 * @var    int
	 **/
	private $m_logLevel = 1;

	/**
	 * Is logging enabled?
	 * @access private
	 * @var    bool
	 **/
	private $m_isEnabled = true;

	public function initialize()
	{
		$this->m_isEnabled = $this->c('Config')->getValue('logging.enabled');

		if (!$this->m_isEnabled)
			return $this;

		$this->m_logFile = STATIC_DIR . '_debug' . DS . 'tmp.dbg';

		$this->m_logLevel = $this->c('Config')->getValue('logging.level');

		return $this;
	}

	/**
	 * Adds some lines to debug message
	 * @param array $args
	 * @param string $type
	 * @return void
	 **/
	private function addLines($args, $type)
	{
		if (!$this->m_isEnabled)
			return;

		$log = $this->applyStyle($type);
		$text = call_user_func_array('sprintf', $args);
		$log .= $text . '<br />' . NL;

		$this->writeData($log);
	}

	/**
	 * Writes error message to log file (if allowed)
	 * @param string $message
	 * @param ...
	 * @return void
	 **/
	public function writeError($message)
	{
		if (!$this->m_isEnabled)
			return;

		$args = func_get_args();	
		$this->addLines($args, 'error');
	}

	/**
	 * Writes component log message to log file (if allowed)
	 * @param string $message
	 * @param ...
	 * @return void
	 **/
	public function writeComponent($message)
	{
		if (!$this->m_isEnabled || $this->m_logLevel < 4)
			return;

		$args = func_get_args();	
		$this->addLines($args, 'component');
	}

	/**
	 * Writes debug message to log file (if allowed)
	 * @param string $message
	 * @param ...
	 * @return void
	 **/
	public function writeDebug($message)
	{
		if (!$this->m_isEnabled || $this->m_logLevel < 2)
			return;

		$args = func_get_args();	
		$this->addLines($args, 'debug');
	}

	/**
	 * Writes sql log message to log file (if allowed)
	 * @param string $message
	 * @param ...
	 * @return void
	 **/
	public function writeSql($message)
	{
		if (!$this->m_isEnabled || $this->m_logLevel < 3)
			return;

		$args = func_get_args();	
		$this->addLines($args, 'sql');
	}

	/**
	 * Applies HTML style to log message
	 * @param string $type
	 * @return string
	 **/	
	private function applyStyle($type)
	{
		if (!$this->m_isEnabled)
			return;

		$date = date('d-m-Y H:i:s');
		switch ($type)
		{
			case 'error':
			case 'debug':
			case 'sql':
			case 'component':
				return '<strong>' . strtoupper($type) . '</strong> [' . $date . ']: ';
			default:
				return '';
		}
	}

	/**
	 * Writes new log message to log file
	 * @param string $data
	 * @return void
	 **/
	private function writeData($data)
	{
		if (!$this->m_isEnabled)
			return;

		file_put_contents($this->m_logFile, $data, FILE_APPEND);
	}
};