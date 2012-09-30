<?php
/**
 * 
 */
class TTemplateTokenControl extends TObject
{
	private $class;
	private $properties;
	private $self_closing = false;
	private $only_closing = false;
	private $childs = array();
	private $parent;
	
	public function __construct($class, array $properties = array(), $self_closing = false, $only_closing = false)
	{
		$this->class = $class;
		$this->properties = $properties;
		$this->self_closing = $self_closing;
		$this->only_closing = $only_closing;
	}
	
	public function AddChild(TTemplateTokenControl $c)
	{
		$this->childs[] = $c;
		$c->SetParent($this);
	}
	
	public function &GetChilds()
	{
		return $this->childs;
	}
	
	public function GetParent()
	{
		return $this->parent;
	}
	
	public function SetParent(TTemplateTokenControl $p)
	{
		$this->parent = $p;
	}
	
	public function AddProperty(TTemplateTokenProperty $p)
	{
		$this->properties[$p->GetName()] = $p->GetValue();
	}
	
	public function SetProp($name, $value)
	{
		$this->properties[$name] = $value;
	}
	
	public function AppendProp($name, $value)
	{
		if(!isset($this->properties[$name])) $this->properties[$name] = '';
		
		$this->properties[$name] .= $value;
	}
	
	public function getClass()
	{
		return $this->class;
	}
	
	public function getProperties()
	{
		return $this->properties;
	}
	
	public function isOpening()
	{
		return !$this->only_closing;
	}
	
	public function isSelfClosing()
	{
		return $this->self_closing;
	}
}
