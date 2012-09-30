<?php
Engine::Using('System.Object');
Engine::Using('System.Http.HttpUrl');
Engine::Using('System.Http.HttpCookie');

/**
 * THttpResponse
 * 
 * 
 *
 */
abstract class THttpResponse extends TObject
{
	private static $_codes = array(
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		307 => 'Temporary Redirect',
		
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Request Range Not Satisfiable',
		417 => 'Expactation Failed',
		
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported',
		600 => 'Malformed URI',
		601 => 'Connection Timed Out',
		602 => 'Unknown Error',
		603 => 'Could Not Parse Reply',
		604 => 'Protocol Not Supported'
	);
	
	public static function setCode($code)
	{
		if(!isset(self::$_codes[$code])) throw new CoreException('Unsupported response code: '.$code);
		
		$codemsg = self::$_codes[$code];
		header("HTTP/1.1 {$code} {$codemsg}");
	}
	
	public static function setHeader($header, $value)
	{
		header($header.': '.$value);
	}
	
	public static function Reload()
	{
		self::setHeader('Location', $_SERVER['REQUEST_URI']);
		exit;
	}
	
	public static function Redirect($url, $code = 301)
	{
		if(is_array($url))
		{
			$url = new THttpUrl($url);
		}
		
		self::setCode($code);
		self::setHeader('Location', (string) $url);
		exit;
	}
	
	public static function setCookie(THttpCookie $cookie)
	{
		header((string) $cookie);
	}
}
