<?php
/**
 * 
 */
class THtmlTag extends TControl
{
	protected $_tagName;
	
	public function setTagName($tagName)
	{
		$this->_tagName = $tagName;
	}
	
	public function getTagName()
	{
		return $this->_tagName;
	}
}
