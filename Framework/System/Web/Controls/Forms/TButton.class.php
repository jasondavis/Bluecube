<?php
/**
 * 
 */
class TButtonEventArgs extends TEventArgs {}

class TButton extends TFormControl
{
	protected $_validationGroup;
	protected $_causesValidation = true;

	public function getTagName()
	{
		return 'input';
	}
	
	public function getAllowChildControls()
	{
		return false;
	}
	
	public function getHasEndTag()
	{
		return false;
	}
	
	public function getText()
	{
		return $this->getViewState('text');
	}
	
	public function setText($text)
	{
		$this->setViewState('text', $text);
	}
	
	public function RaisePostBackEvent()
	{
		$valid = true;
		
		if($this->getCausesValidation())
		{
			foreach($this->getPage()->getControls() as $ctl)
			{
				if($ctl instanceOf TValidator)
				{
					if($ctl->getValidationGroup() == $this->getValidationGroup())
					{
						if(!$ctl->Validate())
						{
							$valid = false;
						}
					}
				}
			}
		}

		if($valid)
		{
			$this->RaiseEvent('onClick', new TButtonEventArgs);	
		}
	}
	
	protected function onRender(TEventArgs $args)
	{
		$this->setAttributeToRender('type','submit');
		$this->setAttributeToRender('value', $this->getViewState('text'));
		
		if($this->getCausesValidation() && ($form = $this->getPage()->FindControl('TForm')))
		{
			$clientId = $this->getClientId();
			$validationGroup = $this->getValidationGroup();
			
			$form->AddScript("if(typeof(TValidator) != 'undefined') { TValidator.causeValidation('click', '$clientId', '$validationGroup'); }");
		}
	}
	
	public function setCausesValidation($causesValidation)
	{
		$this->_causesValidation = TVar::toBool($causesValidation);
	}
	
	public function getCausesValidation()
	{
		return $this->_causesValidation;
	}
	
	protected function onClick(TEventArgs $args) {}
}
