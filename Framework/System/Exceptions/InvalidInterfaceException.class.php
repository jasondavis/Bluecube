<?php
/**
 * 
 */
class InvalidInterfaceException extends Exception
{
	public function __construct($classname, $interface)
	{
		$this->message = "Class $classname must implement $interface";
	}
}