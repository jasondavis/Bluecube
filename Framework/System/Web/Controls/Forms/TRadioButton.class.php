<?php
/**
 * 
 */
class TRadioButton extends TCheckBox
{
	protected function onCreate(TEventArgs $e)
	{
		parent::onCreate($e);
		
		$name = $this->getName();
		
		if(!empty($_POST))
		{
			if(isset($_POST[$name]) && $_POST[$name] == $this->getText())
			{
				$this->setChecked(true);
			}
			else
			{
				$this->setChecked(false);
			}
		}
	}
	
	protected function onRender(TEventArgs $args)
	{
		$this->setAttributeToRender('type','radio');
		$this->setAttributeToRender('value', $this->getText());
		
		if($this->getChecked())
		{
			$this->setAttributeToRender('checked','checked');
		}
	}
}