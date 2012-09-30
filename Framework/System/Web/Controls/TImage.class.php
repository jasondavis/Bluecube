<?php
/**
 * 
 */
class TImage extends TControl
{
	public function getTagName()
	{
		return 'img';
	}
	
	public function getAllowChildControls()
	{
		return false;
	}
	
	public function getHasEndTag()
	{
		return false;
	}
	
	public function setSrc($source)
	{
		$this->setSource($source);
	}
	
	public function getSrc()
	{
		return $this->getSource();
	}
	
	public function setSource($source)
	{
		$this->setViewState('source', $source);
		$this->setAttributeToRender('src', (string) $source);
	}
	
	public function getSource()
	{
		return $this->getViewState('source','');
	}
}