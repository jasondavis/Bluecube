<?php
/**
 * 
 */
class MissingClassException extends Exception
{
	public function __construct($classname)
	{
		$this->message = 'Class '.$classname.' could not be found';
	}
}