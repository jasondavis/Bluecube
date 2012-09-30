<?php
/**
 * 
 */
class TMultiView extends TControl
{
	protected $_views = array();
	
	public function getTagName()
	{
		return null;
	}
	
	public function getAllowChildControls()
	{
		return array('TLiteral', 'TView');
	}
	
	public function setActiveViewIndex($index)
	{
		$index = TVar::toInt($index);
		
		$this->setViewState('activeviewindex', $index);
	}
	
	public function getActiveViewIndex()
	{
		return $this->getViewState('activeviewindex', 0);
	}
	
	public function AddControl(TControl $control)
	{
		parent::AddControl($control);
		
		if($control instanceOf TView)
		{
			$this->_views[] = $control;
		}
	}
	
	public function onRender(TEventArgs $e)
	{
		$active = $this->getActiveViewIndex();
		
		foreach($this->_views as $k => $view)
		{
			if($k == $active)
			{
				$view->Show();
			}
			else
			{
				$view->Hide();
			}
		}
	}
}
