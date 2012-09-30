<?php
/**
 * 
 */
class TListItem extends TControl
{
	public function onCreate(TEventArgs $args)
	{
		$allow_parents = array('TDropDownList');
		
		if(!in_array(get_class($this->getParent()), $allow_parents))
		{
			throw new ControlException('Control TListItem can be placen within '.implode(', ', $allow_parent).' controls');
		}
		
		$this->getParent()->addOption($this);
	}
	
	public function setText($text)
	{
		$this->setViewState('Text', $text);
	}
	
	public function getText()
	{
		return $this->getViewState('Text');
	}
	
	public function setValue($value)
	{
		$this->setViewState('Value', $value);
	}
	
	public function setSelected($selected)
	{
		switch(get_class($this->getParent()))
		{
			case 'TDropDownList':
				$this->setViewState('selected', TVar::toBool($selected));
			break;
		}
	}
	
	public function getSelected()
	{
		return $this->getViewState('selected');
	}
	
	public function getValue()
	{
		return $this->getViewState('Value');
	}
	
	public function getTagName()
	{
		switch(get_class($this->getParent()))
		{
			case 'TDropDownList':
				return 'option';
			break;
		}
	}
	
	public function getAllowChildControls()
	{
		switch(get_class($this->getParent()))
		{
			case 'TDropDownList':
				return false;
			break;
		}
	}
	
	public function getHasEndTag()
	{
		return true;
	}
	
	public function RenderAttributes()
	{
		switch(get_class($this->getParent()))
		{
			case 'TDropDownList':
				if($this->getSelected())
				{
					$this->setAttributeToRender('selected','selected');
				}
				$this->setAttributeToRender('value', $this->getValue());
				$this->setClientId(null);
			break;
		}
		
		parent::RenderAttributes();
	}
	
	public function RenderContent()
	{
		switch(get_class($this->getParent()))
		{
			case 'TDropDownList':
				echo $this->getText();
			break;
		}
	}
}