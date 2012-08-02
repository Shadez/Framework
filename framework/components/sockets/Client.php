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

namespace Sockets;
class Client extends \Component implements \Sockets\ISocket
{
	private $m_socket = null;

	public function createSocket($host, $port, $timeout = 0)
	{
		if (!$host || !$port)
			throw new \Exceptions\ClientSocket('wrong host or port value provided');

		$errno = 0;
		$errmsg = '';

		$this->m_socket = fsockopen($host, $port, $errno, $errmsg, $timeout);

		if (!$this->m_socket)
		{
			$this->c('Log')->writeError('%s : unable to create connection to %s:%d (errno: %d, errmsg: "%s")!', __METHOD__, $host, $port, $errno, $errmsg);

			throw new \Exceptions\ClientSocket('unable to create ClientSocket connection, more info available in logs!');
		}

		return $this;
	}

	public function sendText($text, $socket = null)
	{
		if (!$this->m_socket)
			throw new \Exceptions\ClientSocket('unable to sendText(): no open socket available');

		fputs($this->m_socket, $text);

		return $this;
	}

	public function readText($length)
	{
		if (!$this->m_socket)
			throw new \Exceptions\ClientSocket('unable to readText(): no open socket available');

		return fread($this->m_socket, $length);
	}

	public function closeConnection()
	{
		if (!$this->m_socket)
			return $this;

		fclose($this->m_socket);

		return $this;
	}
}