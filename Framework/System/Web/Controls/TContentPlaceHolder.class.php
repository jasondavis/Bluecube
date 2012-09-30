<?php
/**
 * 
 */
class TContentPlaceHolder extends TControl
{
	private $_content;
	
	public function setContent(TContent $content)
	{
		$this->_content = $content;
	}
	
	public function Render()
	{
		if($this->_content)
		{
		    $this->_content->Render();
		}
	}
}
