<?php
class TActionArgs extends TObject
{
	public function __get($name)
	{
		return THttpRequest::get($name);
	}
	
	public function __set($name, $value)
	{
		throw new InvalidOperationException;
	}
	
	public function __isset($name)
	{
		return THttpRequest::get($name, false) !== false;
	}
}