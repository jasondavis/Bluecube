<?php
/**
 * 
 */
class InvalidAttributeException extends Exception
{
	public function __construct($attribute, $value)
	{
		$this->message = "'$value' is not a valid value of '$attribute' attribute";
	}
}