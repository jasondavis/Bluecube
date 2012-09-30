<?php
/**
 * 
 */
class TRegularExpressionValidator extends TValidator
{
	protected $_regularExpression;
	
	public function setRegularExpression($expression)
	{
		$this->_regularExpression = $expression;
	}
	
	public function getRegularExpression()
	{
		return $this->_regularExpression;
	}
	
	protected function onRender(TEventArgs $e)
	{
		$regex = $this->_regularExpression;
		$regex = str_replace('/', '\/', $regex);
		
		$this->setClientValidateFunction('/'.$regex.'/');
		
		parent::onRender($e);
	}
	
	protected function onServerValidate(TValidatorEventArgs $e)
	{
		$this->setIsValid(preg_match('{'.$this->_regularExpression.'}', $this->getControlToValidateObject()->getText()));
	}
}