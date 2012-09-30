<?php
Engine::Using('System.Object');

abstract class THttpRequest extends TObject
{

	public static function getHash($session_sensitive = true)
	{
		$get = var_export($_GET, true);

		if($session_sensitive && isset($_SESSION) && self::User()->isLoggedIn())
		{
			$cookie = '';//$cookie = var_export($_COOKIE, true);
			$session = var_export($_SESSION, true);
		}
		else
		{
			$cookie = '';
			$session = '';
		}

		return md5(var_export($_SERVER['REQUEST_URI'], true).$get.$cookie.$session);
	}

	public static function Get($name, $default = null)
	{
		return isset($_GET[$name]) ? $_GET[$name] : $default;
	}

	public static function Post($name, $default = null)
	{
		return isset($_POST[$name]) ? $_POST[$name] : $default;
	}

	public static function Cookie($name, $default = null)
	{
		return isset($_COOKIE[$name]) ? $_COOKIE[$name] : $default;
	}

	public static function Session($name, $default = null)
	{
		return isset($_SESSION[$name]) ? $_SESSION[$name] : $default;
	}

	public static function User()
	{
		if(!isset($_SESSION['__system']['user']))
		{
			return new TUser(array(
				'roles' => array('default'),
				'uid' => 0,
				'username' => '',
				'name' => '',
				'password' => '',
				'surname' => ''
			));
		}

		return $_SESSION['__system']['user'];
	}

	/*public static function _mergeArrays()
	{
		static $merged;

		if(!$merged)
		{
			if(!empty($_GET)) foreach($_GET as $k => $v)
			{
				$_REQUEST[$k] = $v;
			}

			if(!empty($_POST)) foreach($_POST as $k => $v)
			{
				$_REQUEST[$k] = $v;
			}

			if(!empty($_COOKIE)) foreach($_COOKIE as $k => $v)
			{
				$_REQUEST[$k] = $v;
			}

			if(!empty($_SESSION)) foreach($_SESSION as $k => $v)
			{
				$_REQUEST[$k] = $v;
			}

			$merged = true;
		}
	}*/

	public static function Host()
	{
		return isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null;
	}

	public static function Port()
	{
		return isset($_SERVER['HTTP_PORT']) ? $_SERVER['HTTP_PORT'] : null;
	}

	public static function RequestMethod()
	{
		return isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : null;
	}

	public static function Protocol()
	{
		return isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : null;
	}

	public static function RequestTime()
	{
		return isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : null;
	}

	public static function QueryString()
	{
		return isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : null;
	}

	public static function Accept()
	{
		return isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : null;
	}

	public static function AcceptCharset()
	{
		return isset($_SERVER['HTTP_ACCEPT_CHARSET']) ? $_SERVER['HTTP_ACCEPT_CHARSET'] : null;
	}

	public static function AcceptEncoding()
	{
		return isset($_SERVER['HTTP_ACCEPT_ENCODING']) ? $_SERVER['HTTP_ACCEPT_ENCODING'] : null;
	}

	public static function AcceptLanguage()
	{
		return isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : null;
	}

	public static function Connection()
	{
		return isset($_SERVER['HTTP_CONNECTION']) ? $_SERVER['HTTP_CONNECTION'] : null;
	}

	public static function Referer()
	{
		return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
	}

	public static function UserAgent()
	{
		return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
	}

	public static function Https()
	{
		return !(isset($_SERVER['HTTPS']) && empty($_SERVER['HTTPS']));
	}

	public static function RemoteAddr()
	{
		return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
	}

	public static function RemotePort()
	{
		return isset($_SERVER['REMOTE_PORT']) ? $_SERVER['REMOTE_PORT'] : null;
	}

	public static function RequestUri()
	{
		return isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null;
	}

	public static function AuthDigest()
	{
		return isset($_SERVER['PHP_AUTH_DIGEST']) ? $_SERVER['PHP_AUTH_DIGEST'] : null;
	}

	public static function AuthUser()
	{
		return isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : null;
	}

	public static function AuthPassword()
	{
		return isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : null;
	}

	public static function AjaxRequest()
	{
		return (isset($_SERVER['X_REQUESTED_WITH']) && $_SERVER['X_REQUESTED_WITH'] == 'XMLHttpRequest');
	}
}
