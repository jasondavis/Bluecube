<?php
/**
 * TObject class
 *
 * All Framework classes inherits from this class
 *
 *
 *
 */
abstract class TObject
{
	public static function Serialize($var)
	{
		return serialize(array($var));
	}

	public static function Unserialize($var)
	{
		if($un = @unserialize($var))
		{
			return $un[0];
		}
		else
		{
			return false;
		}
	}
}
