<?php
/**
 * Authorization manager
 * 
 * Checks whether current user (authorized or anonymous)
 * have access to requested page and action. If not,
 * displays the login page instead requested one.
 * 
 * 
 *
 */

Engine::Using('System.Object');
Engine::Using('System.Security.User');
Engine::Using('System.Interfaces.SessionController');
Engine::Using('System.Interfaces.AuthController');

class TAuthManager extends TObject
{
	/**
	 * Starts session
	 * 
	 * @return unknown_type
	 */
	public static function StartSession()
	{
		$config = Engine::GetConfig('authorization/option[@name="session_controller"]', Engine::SITECONFIG);
		$cookie = Engine::GetConfig('authorization/session', Engine::SITECONFIG);
		
		$controller_class = $config[0]['value'];
		
		if($controller_class != "")
		{
			if(Engine::getMode() == Engine::MODE_DEBUG)
			{
				$controller = new $controller_class;
				
				if(!($controller instanceOf ISessionController))
				{
					throw new InvalidInterfaceException($controller_class, 'ISessionController');
				}
			}
			else
			{
				$controller = new $controller_class;
			}
		
			session_set_save_handler(
				array($controller, 'open'),
				array($controller, 'close'),
				array($controller, 'read'),
				array($controller, 'write'),
				array($controller, 'destroy'),
				array($controller, 'clean')
			);
		}

		if(count($cookie) != 0)
		{
			$lifetime = isset($cookie[0]['lifetime'])	? $cookie[0]['lifetime']				: 0;
			$path = isset($cookie[0]['path'])			? $cookie[0]['path']					: '/';
			$secure = isset($cookie[0]['secure'])		? TVar::toBool($cookie[0]['secure'])	: false;
			$httponly = isset($cookie[0]['httponly'])	? $cookie[0]['httponly']				: true;
			$domain = isset($cookie[0]['domain'])		? $cookie[0]['domain']					: $_SERVER['HTTP_HOST'];
			
			session_cache_expire(round($lifetime/60));
			session_set_cookie_params($lifetime, $path, $domain, $secure, $httponly);
		}
		
		session_start();
		
		$ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'unknown';
		
		$client_hash = $_SERVER['REMOTE_ADDR'].md5($ua);
		
		if(!isset($_SESSION['__system'])) //initialize system session data if not exists
		{
			$_SESSION['__system'] = array(
				'client_hash' => $client_hash
			);
		}
		
		/*
		 * Logout user on user's request or when client hash is different than hash created
		 * when session was initialized. When client hash change occures, this means that
		 * probably the session is stolen.
		 */
		if(isset($_GET['logout']) || $client_hash != $_SESSION['__system']['client_hash'])
		{
			self::Logout();
		}
	}
	
	/**
	 * Logouts user
	 * 
	 * @return unknown_type
	 */
	
	public static function Logout()
	{
		foreach($_SESSION as $k => $v)
		{
			unset($_SESSION[$k]);
		}
		
		session_destroy();
			
		if(isset($_GET['logout'])) unset($_GET['logout']);
			
		THttpResponse::Redirect($_GET);
		exit;
	}
	
	/**
	 * Authorizes user. Returns false on failure or reloads page
	 * on success.
	 * 
	 * @param $username
	 * @param $password
	 * @return unknown_type
	 */
	
	public static function Authorize($username, $password)
	{
		$config = Engine::GetConfig('authorization/option[@name="authorization_controller"]', Engine::SITECONFIG);
		$controller_class = $config[0]['value'];
		
		if(Engine::getMode() == Engine::MODE_DEBUG)
		{
			$controller = new $controller_class;
				
			if(!($controller instanceOf IAuthController))
			{
				throw new InvalidInterfaceException($controller_class, 'IAuthController');
			}
		}
		else
		{
			$controller = new $controller_class;
		}
		
		if(!($userdata = $controller->GetUserData($username, $password)))
		{
			return false;
		}
		else
		{
			$user = new TUser(
				array(
					'roles' => array_merge($userdata['roles'], array('default', 'authorized')),
					'uid' => $userdata['uid'],
					'username' => $userdata['username'],
					'name' => $userdata['name'],
					'surname' => $userdata['surname'],
					'password' => $password
				)
			);
			
			$_SESSION['__system']['user'] = $user;
			
			THttpResponse::Reload();
		}
	}
	
	/**
	 * Checks whether user has access to requested page and action
	 * 
	 * @param $page
	 * @param $action
	 * @return unknown_type
	 */
	
	private static $checks = array();
	
	public static function CheckAuth($page, $action, $checkOnly = false)
	{
		if(!defined('AUTH_UID'))
		{
 	        try
            {
                $user = $_SESSION['__system']['user'];
                define('AUTH_UID', $user->getData('uid'));
                define('AUTH_USERNAME', $user->getData('username'));
            }
            catch(Exception $e)
            {
                define('AUTH_UID', 0);
                define('AUTH_USERNAME', null);
            }
		}
        
		if($page == 'TMediaService')
		{
			return true;	
		}
		
		$check = $page.':'.$action;
		
		if(isset(self::$checks[$check]))
		{
			return self::$checks[$check];
		}
		
		do //check the full page hierarchy against authorization rules
		{	
			$auth = self::CheckAuth2($page, $action, $checkOnly);
			if(!$auth || ($page = get_parent_class($page)) === false)
			{
				$auth = false;
				break;
			}
		}
		while($auth && $page != 'TPage' && $page != 'TService');
		
		self::$checks[$check] = $auth;
		
		return $auth;
	}
	
	private static function CheckAuth2($page, $action, $checkOnly = false)
	{
		try
		{
			$user = $_SESSION['__system']['user'];
		}
		catch(Exception $e)
		{
			$user = new TUser(array(
				'roles' => array('default'),
				'uid' => 0
			));
		}
		
		$compute = true; //whether compute result or use cached value
		
		if(Engine::getMode() == Engine::MODE_PERFORMANCE)
		{
			$cache_key = 'Auth:'.md5($page.$user->getUserKey().$action);
			
			if($cache = Engine::ReadCache($cache_key))
			{
				$allowed = TObject::Unserialize($cache);
				$compute = false;
			}
		}
		
		if($compute)
		{
			$roles = $user->getData('roles');
			$uid = $user->getData('uid');
		
			$rules = array();
		
			foreach($roles as $role)
			{
				$role_path = 'authorization/role[@name="'.$role.'"]';
			
				$rule = Engine::GetConfig($role_path, Engine::SITECONFIG);
				$allow = Engine::GetConfig($role_path.'/allow', Engine::SITECONFIG);
				$deny = Engine::GetConfig($role_path.'/deny', Engine::SITECONFIG);

				if(!isset($rule[0])) continue;
				
				$rules[] = array('order' => $rule[0]['order'], 'allow' => $allow, 'deny' => $deny);
			}
		
			$allowed = self::ComputeRuleset($rules, $page, $action);
		}
		
		if($compute && Engine::getMode() == Engine::MODE_PERFORMANCE)
		{
			Engine::WriteCache(TObject::Serialize($allowed), $cache_key, 0);	
		}
		
		if(!$allowed)
		{
			if(!$checkOnly)
			{
				$login_page = Engine::GetConfig('authorization/option[@name="login_page"]', Engine::SITECONFIG);
				if(!$login_page) throw new CoreException('Login page unknown, check your authorization configuration');
			
				if($page != $login_page[0]['value'])
				{
					$_GET = array('page' => $login_page[0]['value']);
				}
				else
				{
					$_GET['page'] = $login_page[0]['value'];
				}
			}
			
			return false;
		}
		
		return true;
	}
	
	private static function ComputeRuleset($rules, $page, $action)
	{
		foreach($rules as $rule)
		{
			if(Engine::getMode() == Engine::MODE_DEBUG)
			{
				if(!in_array($rule['order'], array('deny,allow', 'allow,deny')))
				{
					throw new CoreException('Unknown order `'.$rule['order'].'`');
				}
			}
			
			$order = explode(',', $rule['order']);
			
			$allow_page = false;
			$allow_action = false;
			
			foreach($order as $ord)
			{
				$check_rules = $rule[$ord];
									
				foreach($check_rules as $check_rule)
				{
					if($check_rule['page'] == '*' || $check_rule['page'] == $page)
					{
						$allow_page = ($ord == 'allow') ? true : false;
						
						if($check_rule['actions'] == '*' || in_array($action, preg_split('{\s?,\s?}', $check_rule['actions'])))
						{
							$allow_action = ($ord == 'allow') ? true : false;
						}
					}
				}
			}
			
			if($allow_page || $allow_action) return true;
		}
		
		return false;
	}
}
