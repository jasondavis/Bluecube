<?php
/**
 * 
 */
Engine::Using('System.Object');

class TVar extends TObject
{
	public static function autoCast($value)
	{
		if($value == 'true' || $value == 'false')
		{
			return self::toBool($value);
		}
		else if(is_numeric($value))
		{
	        if(strpos($value, '.') !== false)
    		{
			    return self::toFloat($value);
    		}
    		else
    		{
    			return self::toInt($value);
    		}
		}
		else
		{
			return $value;
		}
	}
	
	public static function toFloat($value)
	{
		return (float) $value;
	}
	
	public static function toDouble($value)
	{
		return (double) $value;
	}
	
	public static function toInt($value)
	{
		return (int) $value;
	}
	
	public static function toBool($value)
	{
		if(is_string($value))
		{
			switch(strtolower($value))
			{
				case 'true': return true;
				case 'false': return false;
				case '1': return true;
				case '0': return false;
			}
		}
		
		return (bool) $value;
	}
	
	public static function toBoolean($value)
	{
		return self::toBool($value);
	}
	
	public static function toString($value)
	{
		if(is_bool($value))
		{
			if($value)
			{
				return 'true';	
			}
			else
			{
				return 'false';
			}
		}
		
		if(is_array($value))
		{
			return implode(',', $value);
		}
		
		return (string) $value;
	}
	
	public static function toArray($value)
	{
		if(is_array($value))
		{
			return $value;
		}
		
		if(is_string($value))
		{
			$e = explode(',',$value);
			foreach($e as $k => $v)
			{
				$e[$k] = trim($v);
			}
			return $e;
		}
		
		if(is_object($value))
		{
			return (array) $value;
		}
		
		return array($value);
	}
	
	public static function toJSON($value)
	{
		if(function_exists('json_encode'))
		{
			return json_encode($value);
		}
		else
		{
			if(is_null($a)) return 'null';
			if($a === false) return 'false';
			if($a === true) return 'true';
			
			if(is_scalar($a))
			{
				if(is_float($a))
				{
					return floatval(str_replace(",", ".", strval($a)));
				}

				if(is_string($a))
				{
					static $jsonReplaces = array(array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"'), array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'));
					return '"'.str_replace($jsonReplaces[0], $jsonReplaces[1], $a).'"';
				}
				else
				{
					return $a;
				}
			}
			
			$isList = true;
			for($i = 0, reset($a); $i < count($a); $i++, next($a))
			{
				if(key($a) !== $i)
				{
					$isList = false;
					break;
				}
			}
			
			$result = array();
			if($isList)
			{
				foreach($a as $v)
				{
					$result[] = self::toJSON($v);	
				}
				return '['.join(',', $result).']';
			}
			else
			{
				foreach($a as $k => $v)
				{
					$result[] = self::toJSON($k).':'.self::toJSON($v);	
				}
				return '{'.join(',', $result).'}';
			}
		}
	}
}