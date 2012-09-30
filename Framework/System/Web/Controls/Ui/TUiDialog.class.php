<?php
/**
 * 
 */
class TUiDialog extends TUiControl
{
	protected $_buttons = array();
	
	protected function onCreate(TEventArgs $e)
	{
		parent::onCreate($e);
		
		TAssetManager::Publish('/Assets/jQuery/ui/jquery-ui-dialog.js');
	}
	
	public function getTagName()
	{
		return 'div';
	}
	
	public function setTitle($title)
	{
		$this->setViewState('title', $title);
	}
	
	public function getTitle()
	{
		return $this->getViewState('title', '');
	}
	
	public function addButton($caption, $code)
	{
		$this->_buttons[$caption] = $code;
	}
	
	protected function onRender(TEventArgs $e)
	{
		parent::onRender($e);
		
		$this->setAttributeToRender('title', $this->getTitle());
		
	    if($form = $this->getPage()->FindControl('TForm'))
        {
            $config = $this->Config2Json();
            
            $form->AddScript("
                $('#".$this->getClientId()."').dialog($config);
            ");
            
            foreach($this->_buttons as $caption => $code)
            {
            	$form->AddScript("
            	   $('#".$this->getClientid()."').dialog('option', 'buttons', { '$caption': function() { $code } });
            	");
            }
        }
	}
}