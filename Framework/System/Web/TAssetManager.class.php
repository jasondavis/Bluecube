<?php
/**
 * 
 */
class TAssetManager extends TObject
{
	protected static $_published = array();
	
	public static function Publish($path)
	{
		if(isset(self::$_published[$path])) return false;
		
		self::$_published[$path] = 1;
	}
	
	public static function getPublished()
	{
		return array_keys(self::$_published);
	}
}