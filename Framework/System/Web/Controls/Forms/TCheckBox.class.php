<?php
/**
 * 
 */
class TCheckBox extends TFormControl
{
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
	
	protected function onRender(TEventArgs $args)
	{
		$this->setAttributeToRender('type','checkbox');
		$this->setAttributeToRender('value', $this->getName());
		
		if($this->getChecked())
		{
			$this->setAttributeToRender('checked','checked');
		}
	}
	
	protected function onCreate(TEventArgs $e)
	{
		parent::onCreate($e);
		
		$name = $this->getName();
		
		if(!empty($_POST))
		{
			if(isset($_POST[$name]))
			{
				$this->setChecked(true);
			}
			else
			{
				$this->setChecked(false);
			}
		}
	}
	
	public function setChecked($checked)
	{
		$this->setViewState('checked', TVar::toBool($checked));
	}
	
	public function getChecked()
	{
		return $this->getViewState('checked', false);
	}
	
}