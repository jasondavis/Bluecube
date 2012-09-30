<?php
/**
 * PhpErrorException class
 *
 * All PHP errors are turned into this exception automatically
 *
 * 
 *
 */

class PhpErrorException extends Exception
{
	public function __construct($errno, $errstr, $errfile, $errline, $errcontext)
	{
		$this->message = $errstr;
		$this->code = $errno;
		$this->file = $errfile;
		$this->line = $errline;
	}
}


/**
 * TErrorHandler class
 * 
 * This class handles all handleable PHP errors and
 * turn them into PhpErrorException
 *
 * 
 *
 */

class TErrorHandler extends TObject
{
	public function Handle($errno, $errstr, $errfile, $errline, $errcontext)
	{
		if(error_reporting() == 0)
		{
			return;
		}
		
		throw new PhpErrorException($errno, $errstr, $errfile, $errline, $errcontext); 
	}
}

set_error_handler(array(new TErrorHandler, 'Handle'));
