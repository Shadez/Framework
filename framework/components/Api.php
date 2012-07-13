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

class Api_Component extends Component
{
	protected $m_apiInitialized = false;
	protected $m_apiMethods = array();
	protected $m_apiRequests = array();
	protected $m_apiResponse = array();
	protected $m_apiLevels = array();
	protected $m_apiDisabledMethods = array();
	protected $m_apiSignature = array();
	protected $m_apiErrorResponse = array(
		'errno' => 1,
		'errmsg' => 'Unable to run this method',
	);

	public function initialize()
	{
		if ($this->c('Config')->getValue('api.disabled'))
		{
			$this->m_apiErrorResponse = array(
				'errno' => -999,
				'errmsg' => 'API Feature was disabled on this site',
			);

			$this->m_apiInitialized = false;

			return $this;
		}

		require_once(APP_DIR . 'API.php');

		$this->m_apiMethods = array();
		$this->m_apiDisabledMethods = array();

		foreach($APIMethods as $type => $methods)
		{
			if (!$type || !$methods)
				continue;

			foreach ($methods as $method)
			{
				if (!$method)
					continue;

				$this->m_apiMethods[$method['request']] = $method;

				if ($method['disabled'])
					$this->m_apiDisabledMethods[] = $method;
			}
		}

		unset($APIMethods, $type, $methods, $method);

		$this->m_apiInitialized = true;

		return $this;
	}

	/**
	 * Returns all available API methods defined in API methods holder
	 * @return array
	 **/
	public function getApiMethods()
	{
		return $this->m_apiMethods;
	}

	/**
	 * Checks API user signature to grant access to some methods
	 * @return bool
	 **/
	public function checkSignature()
	{
		if (!$this->m_apiSignature)
			$this->m_apiSignature = $this->c('Events')->triggerEvent('onApiSignatureCheck', array('sig' => isset($_GET['apiSig']) ? $_GET['apiSig'] : ''), $this);

		return isset($this->m_apiSignature['sig']);
	}

	/**
	 * Returns API user signature data
	 * @return array
	 **/
	public function getApiSignature()
	{
		return $this->m_apiSignature;
	}

	/**
	 * Returns specific data of API user signature
	 * @return mixed
	 **/
	public function getApiSignatureData($data)
	{
		return isset($this->m_apiSignature[$data]) ? $this->m_apiSignature[$data] : false;
	}

	/**
	 * Checks if provided method is allowed be be runned for current API user
	 * @param string $method
	 * @return bool
	 **/
	public function isAllowedToRun($method)
	{
		if ($this->m_apiDisabledMethods)
		{
			foreach ($this->m_apiDisabledMethods as &$m)
			{
				if ($m['request'] == $method)
					return false;
			}
		}

		if (!$this->checkSignature())
			return false;

		return true;
	}

	/**
	 * Returns API method's error response (or default if nothing provided)
	 * @param int $errno = 0
	 * @param string $errmsg = ''
	 * @return array
	 **/
	public function getErrResp($errno = 0, $errmsg = '')
	{
		$errresp = $this->m_apiErrorResponse;

		if (!$errno && !$errmsg)
			return $errresp;

		if ($errno != 0)
			$errresp['errno'] = $errno;
		if ($errmsg)
			$errresp['errmsg'] = $errmsg;

		return $errresp;
	}

	/**
	 * Checks if provided method is a real method
	 * @param string $method
	 * @return bool
	 **/
	protected function isApiMethod($method)
	{
		return isset($this->m_apiMethods[$method]);
	}

	/**
	 * Returns method's type
	 * @param string $method
	 * @return mixed
	 **/
	protected function getMethodType($method)
	{
		return $this->isApiMethod($method) ? $this->m_apiMethods[$method]['type'] : false;
	}

	/**
	 * Returns method info by name
	 * @param string $name
	 * @return array
	 **/
	protected function getMethod($method)
	{
		if ($this->isApiMethod($method))
		{
			$a = $this->m_apiMethods[$method];
			$a['apiSignature'] = $this->m_apiSignature;

			return $a;
		}

		return array();
	}

	/**
	 * Tries to execute API method
	 * @param string $method
	 * @return array
	 **/
	protected function runApi($method)
	{
		if (!$this->m_apiInitialized)
			return $this->m_apiErrorResponse;

		if (!$this->checkSignature())
			return $this->getErrResp(-2, 'Wrong API Signature provided');
		if (!$this->isApiMethod($method))
			return $this->getErrResp(2, 'Unknown method');
		if (!$this->isAllowedToRun($method))
			return $this->getErrResp();

		$apiData = array();
		$apiMethod = $this->getMethod($method);

		if (!$apiMethod)
			return $this->getErrResp(2, 'Unknown method');

		if ($apiMethod['disabled'])
			return $this->getErrResp(4, 'This method was disabled');

		if ($apiMethod['argc'] > 0)
		{
			if (isset($apiMethod['post']) && $apiMethod['post'])
				$holder = $_POST;
			else
				$holder = $_GET;
			foreach ($apiMethod['argk'] as $k => $t)
			{
				if (!isset($holder[$k]))
					return $this->getErrResp(3, 'Not enough actual parameters');

				if (is_string($holder[$k]))
					$apiData[$k] = addslashes(urldecode($holder[$k]));
				else
					$apiData[$k] = $holder[$k];

				switch ($t)
				{
					case 'int':
						$apiData[$k] = (int) $apiData[$k];
						break;
					case 'float':
						$apiData[$k] = (float) $apiData[$k];
						break;
					case 'double':
						$apiData[$k] = (double) $apiData[$k];
						break;
					case 'bool':
						if ($apiData[$k] == 'true')
							$apiData[$k] = true;
						elseif ($apiData[$k] == 'false')
							$apiData[$k] = false;
						break;
					case 'string':
					case 'array':
					default:
						break;
				}
			}
		}

		if (sizeof($apiData) != $apiMethod['argc'])
			return $this->getErrResp(3, 'Not enough actual parameters');

		if ($this->c('SiteApi')->isApiMethodImplemented($apiMethod['name']))
			$this->m_apiResponse = $this->c('SiteApi')->runApiMethod($apiMethod, $apiData);
		elseif (method_exists($this, 'apiMethod_' . $apiMethod['name']))
		{
			call_user_func_array(array($this, 'apiMethod_' . $apiMethod['name']), array(
				'method' => $apiMethod,
				'data' => $apiData
			));
		}
		else
			$this->m_apiResponse = $this->getErrResp(2, 'Unknown method');

		if (!$this->m_apiResponse)
			$this->m_apiResponse = $this->getErrResp(2, 'Unknown method');

		if (!isset($this->m_apiResponse['errno']))
			$this->m_apiResponse['errno'] = 0;
		if (!isset($this->m_apiResponse['errmsg']))
			$this->m_apiResponse['errmsg'] = 'none';

		ksort($this->m_apiResponse);

		return $this->m_apiResponse;
	}

	/**
	 * Public wrapper for Api_Component::runApi method
	 * @param string $method
	 * @returns array
	 **/
	public function runApiMethod($method)
	{
		return $this->runApi($method);
	}

	/**
	 * Handler for Core.getRawUrl API method. Listed as example
	 * @param string $method
	 * @param array $data
	 * @return void
	 **/
	protected function apiMethod_coregetrawurl($method, $data)
	{
		$this->m_apiResponse = array(
			'rawUrl' => $this->getCore()->getRawUrl()
		);
	}

	/**
	 * Handler for Core.getUrlAction API method. Listed as example
	 * @param string $method
	 * @param array $data
	 * @return void
	 **/
	protected function apiMethod_coregeturlaction($method, $data)
	{
		$this->m_apiResponse = array(
			'action' => $this->getCore()->getUrlAction($data['idx'])
		);
	}

	/**
	 * Handler for Core.getVersion API method. Listed as example
	 * @param string $method
	 * @param array $data
	 * @return void
	 **/
	protected function apiMethod_coregetversion($method, $data)
	{
		$this->m_apiResponse = array();

		if ($data['fullVersion'])
			$this->m_apiResponse['version'] = '0.5.12.2;0.5';
		else
			$this->m_apiResponse['version'] = '0.5';

		if ($data['info'])
			$this->m_apiResponse['info'] = 'Shadez Framework: Core Version 0.5.12.2, API Version 0.5';
		else
			$this->m_apiResponse['info'] = '';
	}
};