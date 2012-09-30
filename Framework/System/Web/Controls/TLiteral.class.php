<?php
/**
 * 
 */
class TLiteral extends TControl
{
	public function getTagName()
	{
		return null;
	}
	
	public function getAllowChildControls()
	{
		return false;
	}
	
	public function getEnableViewState()
	{
		return false;
	}
	
	public function setText($text)
	{
		$this->setViewState('text', $text);
	}
	
	public function getText()
	{
		return $this->getViewState('text','');
	}
	
	public function RenderContent()
	{
		echo $this->getText();
	}
}
