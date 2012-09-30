<?php
/**
 * 
 */

Engine::Using('System.Object');

class TStyle extends TObject implements ArrayAccess
{
	protected $_style = array();
	
	public function __construct($styleStr)
	{
		$parts = explode(';', $styleStr);
		
		if(!empty($parts)) foreach($parts as $part)
		{
			$part = trim($part);
			if($pos = strpos($part,':'))
			{
				$attrib = trim(strtolower(substr($part,0,$pos)));
				$value = trim(substr($part,$pos+1));
				
				$this->_style[$attrib] = $value;
			}
		}
	}
	
	public function offsetSet($name, $value)
	{
		$this->Set($name, $value);
	}
	
	public function offsetGet($name)
	{
		return $this->Get($name);
	}
	
	public function offsetUnset($name)
	{
		$name = strtolower($name);
		
		if(isset($this->_style[$name]))
		{
			unset($this->_style[$name]);
		}
	}
	
	public function offsetExists($name)
	{
		$name = strtolower($name);
		
		return isset($this->_style[$name]);
	}
	
	public function Set($attrib, $value)
	{
		$attrib = strtolower($attrib);
		
		$this->_style[$attrib] = $value;
	}
	
	public function Get($attrib)
	{
		$attrib = strtolower($attrib);
		
		return $this->_style[$attrib];
	}
	
	public function __toString()
	{
		$ret = '';
		
		foreach($this->_style as $attrib => $value)
		{
			$ret .= "$attrib:$value;";
		}
		
		return $ret;
	}
}