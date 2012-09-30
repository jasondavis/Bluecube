<?php
/**
 * TMemoizer class implementing the memoization pattern.
 * 
 * Usage (examples):
 * 
 * 1. Object method call caching
 * 
 * $memDb = new TMemoizer(new SomeClass);
 * $ret = $memDb->DoSomething();
 * 
 * 2. Static method call caching
 * 
 * $memDb = new TMemoizer('className');
 * $ret = $memDb->DoSomethingStatic();
 * 
 * 2. Function call caching
 * 
 * $mem = new TMemoizer;
 * $upper = $mem->strtoupper('some text');
 *
 * 
 * 
 */

class TMemoizer extends TObject
{
	protected $_target;
	protected $_max_exec_time = 0; //milliseconds, 0 - cache everything
	protected $_expires = 0;
	
	public function __construct($target = null, $max_exec_time = 0, $expires = 0)
	{
		$this->_target = $target;
		$this->_max_exec_time = $max_exec_time;
		$this->_expires = $expires;
	}
	
	public function __call($method, $args)
	{
		$cache_key = '__memoize_'.md5(var_export($this->_target, true).var_export($method, true).var_export($args, true));
		
		try
		{
			if($cached = TCache::Read($cache_key))
			{
				return $cached;
			}
		}
		catch(Exception $e) //something went wrong...
		{
			
		}
		
		$time_start = microtime(true);
		if(is_object($this->_target) || is_string($this->_target))
		{
			$return = call_user_func_array(array($this->_target, $method), $args);
		}
		else
		{
			$return = call_user_func_array($method, $args);
		}
		$time_end = microtime(true);
		
		try
		{
			if($this->_max_exec_time == 0 || ($time_end-$time_start) > $this->_max_exec_time)
			{
				TCache::Write($cache_key, $return, $this->_expires);
			}
		}
		catch(Exception $e) //something went wrong...
		{
			
		}
		
		return $return;
	}
	
	public function __set($name, $value)
	{
		if(!is_object($this->_target)) throw new InvalidOperationException('Target must be an object');
		
		$this->_target->$name = $value;
	}
	
	public function __get($name)
	{
		if(!is_object($this->_target)) throw new InvalidOperationException('Target must be an object');
		
		return $this->_target->$name;
	}
}