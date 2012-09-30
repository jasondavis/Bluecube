<?php
/**
 * 
 */
class TRequiredFieldValidator extends TValidator
{
	
	protected function onCreate(TEventArgs $e)
	{
		parent::onCreate($e);
		$this->setClientValidateFunction("if(control.attr('type') == 'checkbox' || control.attr('type') == 'radio') return control.attr('checked'); return $.trim(control.val()) != '';");
	}

	protected function onServerValidate(TValidatorEventArgs $e)
	{
		$ctl = $this->getControlToValidateObject();
		
		if($ctl instanceOf TCheckBox)
		{
			$this->setIsValid($ctl->getChecked());
			return;
		}

		if($ctl instanceOf TTextBox) {
			$this->setIsValid(trim($ctl->Text) != '');
			return;
		}

		if($ctl instanceOf TFileUpload)
		{
			$this->setIsValid($ctl->HasFile());
			return;
		}
	}
}
?>