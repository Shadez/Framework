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

class Core_Component extends Component
{
	const pattern_for_clear_url = '/[^ \/_0-9A-Za-zА-Яа-я-]/';
	private $m_configs 		 = null;
	private $m_session 		 = null;
	private $m_page    		 = null;
	private $m_document      = null;
	private $m_localeHandler = null;
	private $m_actions 	 	 = array();
	private $m_actionsCount  = 0;
	private $m_variables	 = array();
	private $m_terminated	 = false;
	private $m_urlLocale	 = '';
	private $m_rawUrl		 = '';
	private $m_isMobileAgent = false;
	private $m_isInstalled   = false;
	private $m_header		 = '';
	private $m_rewriteRules  = array();

	/**
	 * Separate constructor for Core_Component class
	 * Because of Component::__constuct() requires $core as second argument,
	 * we need to construct Core_Component as the first application available class.
	 * Without it, Component will go to loop and die after 100 iterations.

	 * @access public
	 * @constructor
	 */
	public function __construct()
	{
		$this->core = $this;
		$this->m_component = 'Core';

		if (!isset(self::$m_components['default']))
			self::$m_components['default'] = array();

		self::$m_components['default']['Core'] = $this;

		if (!defined('CLIENT_FILES_PATH'))
			define('CLIENT_FILES_PATH', $this->c('Config')->getValue('site.path'));

		$this->detectMobile()->detectInstallation();
	}

	private function detectMobile()
	{
		$s = $_SERVER['HTTP_USER_AGENT'];

		if (preg_match('/android.+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i', $s) ||
			preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|e\-|e\/|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(di|rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|xda(\-|2|g)|yas\-|your|zeto|zte\-/i', substr($s, 0, 4)))
		{
			$this->m_isMobileAgent = true;
		}

		return $this;
	}

	private function detectInstallation()
	{
		$this->m_isInstalled = file_exists(LOCKERS_DIR . '.installed');

		return $this;
	}

	public function isInstalled()
	{
		return $this->m_isInstalled;
	}

	public function isMobile()
	{
		return $this->m_isMobileAgent;
	}

	public function initialize()
	{
		$this->m_configs	   = $this->c('Config');
		$this->m_session  	   = $this->c('Session');
		$this->m_document 	   = $this->c('Document');
		$this->m_localeHandler = $this->c('Locale');

		// Load Rewrite Rules
		include(INCLUDES_DIR . 'Rewrite.php');

		if (isset($RewriteRules))
		{
			$this->m_rewriteRules = $RewriteRules;
			unset($RewriteRules);
		}

		return $this;
	}

	/**
	 * Creates Core_Component instance
	 * @access public
	 * @return Core_Component
	 **/
	public static function create()
	{
		$core = new Core_Component();

		return $core->initialize();
	}

	/**
	 * Executes all requried actions
	 * @access public
	 * @return Core_Component
	 **/
	public function execute()
	{
		// Parse url string
		$this->parseUrl();

		// Call router BEFORE controllers!
		$this->c('Router');

		// Perform RunOnce
		$this->c('RunOnce', 'Run');

		// IMPORTANT: this is when and where controller being created.
		$this->initController();

		return $this;
	}

	/**
	 * Shuts down the application
	 * @access public
	 * @return Core_Component
	 **/
	public function shutdown()
	{
		Component::prepareShutdown();
		foreach ($this as &$type)
			unset($type);
	}

	/**
	 * Parses URL string ($_SERVER['REQUEST_URI'])
	 * @access private
	 * @return Core_Component
	 **/
	private function parseUrl()
	{
		$url_index = $this->c('Config')->getValue('site.url_string_index');

		if (!$url_index)
			throw new CoreCrash_Exception_Component('unable to find url string index!');

		$url = isset($_GET[$url_index]) ? $_GET[$url_index] : '';

		$this->m_rawUrl = $url;

		$url_data = explode('/', $url);

		if ($url_data)
		{
			$index = 0;
			foreach ($url_data as $action)
			{
				if (!$action)
					continue;

				$this->m_actions['action' . $index] = $action;
				++$index;
			}
		}

		$this->m_actionsCount = $index;

		return $this;
	}

	public function getUrlLocale()
	{
		return $this->m_urlLocale;
	}

	/**
	 * Checks whether URL action index is allowed locale string
	 * @access private
	 * @param  string $action
	 * @param  int $index
	 * @return bool
	 **/
	private function isLocale($action, $index)
	{
		if (!in_array($index, $this->c('Config')->getValue('site.locale_indexes')))
			return false;

		if (!$this->c('Locale')->isLocale($action, $this->c('Locale')->GetLocaleIDForLocale($action)))
			return false;

		// $action is correct locale, set it.
		$this->c('Locale')->setLocale($action, $this->c('Locale')->GetLocaleIDForLocale($action), true);
		return true;
	}

	/**
	 * Performs controller initialization
	 * @access private
	 * @return Core_Component
	 **/
	private function initController()
	{
		if (defined('SKIP_CONTROLLER'))
			return $this;

		// Check rewrite rules
		$controller_name = $this->getRewriteRuleController();

		if (!$controller_name)
		{
			$controller_name = str_replace(' ', '', $this->getUrlAction(0));
			$controller_name = preg_replace('/[^ \/_A-Za-z-]/', '', $controller_name);
		}

		if (!$controller_name || $this->c('Config')->getValue('site.home_only'))
			return $this->c('Home', 'Controller');

		return $this->c($controller_name, 'Controller');
	}

	/**
	 * Checks rewrite rule with current URL and returns controller name (if found any)
	 * @access private
	 * @return string
	 **/
	private function getRewriteRuleController()
	{
		$url = strtolower($this->getRawUrl());

		if (isset($this->m_rewriteRules[$url]))
			return $this->m_rewriteRules[$url];

		return null;
	}

	/**
	 * Returns URL action with index $index
	 * @access public
	 * @param  int $index
	 * @return string
	 **/
	public function getUrlAction($index)
	{
		if ($index < 0 || $index >= $this->m_actionsCount)
			return false;

		return $this->m_actions['action' . $index];
	}

	public function getActions()
	{
		return $this->m_actions;
	}

	public function getActionsCount()
	{
		return $this->m_actionsCount;
	}

	/**
	 * Sets global variable
	 * @access public
	 * @param  string $varName
	 * @param  mixed $varValue
	 * @return Core_Component
	 **/
	public function setVar($varName, $varValue)
	{
		$this->m_variables[$varName] = $varValue;

		return $this;
	}

	/**
	 * Returns global variable with $varName name
	 * @access public
	 * @param  string $varName
	 * @return mixed
	 **/
	public function getVar($varName)
	{
		return isset($this->m_variables[$varName]) ? $this->m_variables[$varName] : null;
	}

	/**
	 * Terminates script and shows error message.
	 * @access public
	 * @param  string $errorMessage = ''
	 * @return void
	 **/
	public function terminate($errorMessage = '')
	{
		$this->m_terminated = true;

		echo '<h1>Unable to load site</h1>' . NL . '<p>Script work was terminated ';
		if ($errorMessage)
			echo 'with message <strong>"' . $errorMessage . '"</strong>!';
		else
			echo 'due to fatal error(s) in core code!';

		$admin_email = $this->c('Config')->getValue('misc.admin_email');
		echo '</p>' . NL . '<p>Please, contact with administrator of this resource via E-Mail <a href="mailto:' . $admin_email . '">' . $admin_email . '</a>.</p>';

		exit(1);
	}

	/**
	 * Returns raw URL
	 * @access public
	 * @return string
	 **/
	public function getRawUrl()
	{
		return $this->m_rawUrl;
	}

	/**
	 * Returns current URL (built with m_actions)
	 * @access public
	 * @return string
	 **/
	public function getAppUrl()
	{
		return implode('/', $this->m_actions);
	}

	/**
	 * Returns all core variables (that were setted via Core_Component::setVar())
	 * @access public
	 * @return array
	 **/
	public function getCoreVars()
	{
		return $this->m_variables;
	}

	/**
	 * Redirect to $path (application URL included)
	 * @access public
	 * @param  string $path = ''
	 * @param  int $code = 302
	 **/
	public function redirectUrl($path = '', $code = 302)
	{
		header('Location: ' . $this->getUrl($path), true, $code);
		exit;
	}

	/**
	 * Redirect to $path (application URL excluded)
	 * @access public
	 * @param  string $path = ''
	 * @param  int $code = 302
	 **/
	public function redirectApp($path, $code = 302)
	{
		header('Location:' . $path, true, $code);
		exit;
	}

	/**
	 * Add custom header
	 * @param  string $header
	 * @return Core_Component
	 **/
	public function addHeader($header)
	{
		$this->m_header .= $header . NLCR;

		return $this;
	}

	/**
	 * Sends header to user agent
	 * @access public
	 **/
	public function sendHeaders()
	{
		header($this->m_header);
	}
}