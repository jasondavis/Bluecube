<?php
/**
 * 
 */
class TLabel extends TText
{
	protected $_forControl;
	
	public function setForControl($control_id)
	{
		$this->_forControl = $control_id;
	}
	
	public function getForControl($control_id)
	{
		return $this->_forControl;
	}
	
	public function getTagName()
	{
		return 'label';
	}
}