<?php
/**
 * 
 */
class TView extends TControl
{
	public function getTagName()
	{
		return null;
	}
	
	protected function onCreate(TEventArgs $e)
	{
		$this->setViewState('visible', false);
	}
}