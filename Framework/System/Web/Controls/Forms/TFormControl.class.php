<?php
/**
 * 
 */
abstract class TFormControl extends TControl
{
	protected $_validationGroup;
	
	protected function onCreate(TEventArgs $args)
	{
		$name = $this->getViewState('name', $this->getClientId());
		
		$this->setName($name);
	}
	
	public function getName()
	{
		return $this->getViewState('name');
	}
	
	public function setName($name)
	{
		$this->setAttributeToRender('name', $name);
		$this->setViewState('name', $name);	
	}
	
	public function setValidationGroup($validationGroup)
	{
		$this->_validationGroup = $validationGroup;
	}
	
	public function getValidationGroup()
	{
		return $this->_validationGroup;
	}
}
 