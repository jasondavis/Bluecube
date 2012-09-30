<?php
/**
 * 
 */
class ResponseError extends Exception
{
	protected $_codes = array(
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
	
	public function __construct($code = 500)
	{
		@ob_clean();
		if(isset($this->_codes[$code]))
		{
			header('HTTP/1.1 '.$code.' '.$this->_codes[$code]);
		}
		
		$path1 = SITE_ROOT.DS.'ErrorPages'.DS.$code.'.html';
		$path2 = ROOT_DIR.DS.'ErrorPages'.DS.$code.'.html';
		
		if(file_exists($path1))
		{
			echo file_get_contents($path1);
		}
		else if(file_exists($path2))
		{
			echo file_get_contents($path2);
		}
		else
		{
			if(isset($this->_codes[$code]))
			{
				echo $this->_codes[$code];
			}
			else
			{
				echo 'Error '.$code;
			}
		}
		exit;
	}
}
