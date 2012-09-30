<?php
/**
 * 
 */
class TCompareValidator extends TValidator
{
	
	protected $_compareToControl;
	
	protected function onCreate(TEventArgs $e)
	{
		parent::onCreate($e);
		$this->setClientValidateFunction("return control.val() == $('#{$this->_compareToControl}').val();");
	}
	
	protected function setCompareToControl($ctl)
	{
		$this->_compareToControl = $ctl;
	}

	protected function onServerValidate(TValidatorEventArgs $e)
	{
		$ctl = $this->getControlToValidateObject();
		
		$id = $this->_compareToControl;
		$compare = $this->getParentTemplateControl()->$id;
		
		if($ctl instanceOf TTextBox)
		{
			$this->setIsValid($ctl->getText() == $compare->getText());
			return;
		}
	}
}
?>