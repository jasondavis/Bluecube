<?php
/**
 * 
 */
class TTemplateTokenProperty extends TObject
{
	private $name;
	private $value;
	private $is_opening;
	
	public function __construct($name, $value, $is_opening = false)
	{
		$this->name = $name;
		$this->value = $value;
		$this->is_opening = $is_opening;
	}
	
	public function AppendValue($value)
	{
		$this->value .= $value;
	}
	
	public function GetName()
	{
		return $this->name;
	}
	
	public function GetValue()
	{
		return $this->value;
	}
	
	public function isOpening()
	{
		return $this->is_opening;
	}
}
