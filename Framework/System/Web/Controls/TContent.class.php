<?php
/**
 * 
 */
class TContent extends TControl
{
	private $_contentPlaceHolderId;
	private $_now_render = false;
	
	public function setContentPlaceHolderId($id)
	{
		$this->_contentPlaceHolderId = $id;
	}
	
	public function getContentPlaceHolderId()
	{
		return $this->_contentPlaceHolderId;
	}
	
	public function getTagName()
	{
		return null;
	}
	
	public function Render()
	{
		if(!$this->_now_render)
		{
		    $contentPlaceHolder = TPage::getControl($this->_contentPlaceHolderId);
		
		    $contentPlaceHolder->setContent($this);
		    $this->_now_render = true;
		}
		else
		{
			parent::Render();
		}
	}
}
