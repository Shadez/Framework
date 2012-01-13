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

defined('BOOT_FILE') || die('Direct access to this file is not allowed!');

class AppCrash
{
	protected $e = null;
	protected $m_appCrashType = null;
	protected $m_appCrashMessage = '';
	protected $m_appCrashDump = '';
	protected $m_exceptionInfo = array();

	public function __construct(Exception $e)
	{
		if (!$e)
			return false;

		$this->e = $e;

		$this->analyze();

		return $this;
	}

	protected function analyze()
	{
		if (!$this->e)
			return $this;

		$this->m_appCrashMessage = $this->e->getMessage();

		$class = get_class($this->e);
		if (strtolower($class) == 'exception')
			$this->m_appCrashType = 'default';
		else
			$this->m_appCrashType = substr($class, 0, strpos($class, '_'));

		return $this->generateStackTrace();
	}

	public function getCrashFileName()
	{
		$file = explode(DS, $this->e->getFile());

		if (!$file)
			return '<Unknown file>';

		return $file[sizeof($file) - 1];
	}

	public function getException()
	{
		return $this->e;
	}

	protected function getLines($file, $line)
	{
		$contents = array();
		$lines = explode(NL, file_get_contents($file));
		if ($lines)
		{
			$line_start = max(1, ($line - 10));
			$line_end = min(sizeof($lines), ($line + 10));
			
			$limit = ($line_end - $line_start);
			for ($i = 0; $i < $limit; ++$i)
			{
				$contents[] = array(
					'line' => $line_start + $i,
					'content' => $lines[$line_start + $i]
				);
			}
			unset($lines);
		}
		return $contents;
	}

	protected function generateStackTrace()
	{
		$this->m_appCrashDump = array();
		$this->m_exceptionInfo['lines'] = $this->getLines($this->e->getFile(), $this->e->getLine());
		$trace = $this->e->getTrace();
		$dump = array();
		$index = 0;
		foreach ($trace as $item)
		{
			foreach($item as $key => $value)
			{
				if ($key == 'args')
					continue;
				else if ($key == 'file')
					$dump[$index]['lineContents'] = $this->getLines($value, $item['line']);

				$dump[$index][$key] = $value;
			}
			++$index;
		}

		$this->m_appCrashDump = $dump;

		unset($dump, $trace);

		return $this;
	}

	public function getTrace()
	{
		return $this->m_appCrashDump;
	}

	public function getExceptionInfo($type)
	{
		if (!$this->m_exceptionInfo)
			return false;

		return isset($this->m_exceptionInfo[$type]) ? $this->m_exceptionInfo[$type] : false;
	}

	public function getType()
	{
		return $this->m_appCrashType;
	}

	public function getMessage()
	{
		return $this->m_appCrashMessage;
	}
}