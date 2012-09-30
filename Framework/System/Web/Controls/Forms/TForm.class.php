<?php
/**
 * 
 */
class TForm extends TControl
{
	protected $_script = array('jq' => array(), 'raw' => array());
	protected $_method = 'post';
	protected $_action = false;
	
	protected function setAction($action)
	{
	   $this->_action = $action;
	}
	
	public function getTagName()
	{
		return 'form';
	}
	
	public function setMethod($method)
	{
		$this->_method = $method;
	}
	
	public function getMethod()
	{
		return $this->_method;
	}
	
	public function onRender(TEventArgs $args)
	{
		$this->setAttributeToRender('method', $this->_method);
		$this->setAttributeToRender('action', $this->_action ? $this->_action : $_SERVER['REQUEST_URI']);
	}
	
	public function AddScript($script)
	{
		$this->_script['jq'][] = $script;
	}
	
	public function AddRawScript($script)
	{
		$this->_script['raw'][] = $script;
	}
	
	public function RenderContent()
	{
		parent::RenderContent();
		
		if($this->_method == 'post')
		{
			echo "<div>\n";
			echo '<input type="hidden" id="VIEWSTATE" name="__VIEWSTATE" value="'.wordwrap(TViewState::SerializeData(), 130, "\n\t\t", true).'" />'."\n\t";
			echo '<input type="hidden" id="EVENTTARGET" name="__EVENTTARGET" />'."\n\t";
			echo '<input type="hidden" id="EVENTARGS" name="__EVENTARGS" />'."\n";
			echo "</div>\n";
		}
		if(!empty($this->_script['jq']))
		{
			echo "<script type=\"text/javascript\">\nif(typeof($) != 'undefined') { $(function() {\n";
			foreach($this->_script['jq'] as $script)
			{
				echo trim($script)."\n";
			}
			echo "});}\n</script>\n";
		}
		
		if(!empty($this->_script['raw']))
		{	
			echo "<script type=\"text/javascript\">\n";
			foreach($this->_script['raw'] as $script)
			{
				echo trim($script)."\n";
			}
			echo "</script>";
		}
	}
	
	public function getEnableViewState()
	{
		return false;
	}
}