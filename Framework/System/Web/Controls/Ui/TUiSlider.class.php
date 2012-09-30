<?php
/**
 * 
 */
class TUiSlider extends TUiControl
{
	protected $_valueField;
	
	protected function onCreate(TEventArgs $e)
	{
		parent::onCreate($e);
		
		TAssetManager::Publish('/Assets/jQuery/ui/jquery-ui-slider.js');
		
		$this->_valueField = new THiddenField;
		$this->AddControl($this->_valueField);
		$this->_valueField->RaiseEvent('onCreate', new TEventArgs);
		$this->_valueField->RestoreViewState();
		
		if($this->isPostBack() && isset($_POST[$this->_valueField->getClientId()]))
		{
			$v = $_POST[$this->_valueField->getClientId()];
		    $this->setValue($v);
		    $this->_valueField->Text = $v;
		}
	}
	
	public function getTagName()
	{
		return 'div';
	}
	
	public function setMinValue($value)
	{
		$this->setConfigValue('min', $value);
	}
	
	public function setMaxValue($value)
	{
		$this->setConfigValue('max', $value);
	}
	
	public function setValue($value)
	{
		$this->setViewState('value', $value);
		$this->setConfigValue('value', $value);
	}
	
	public function getValue()
	{
		return $this->getViewState('value');
	}
	
	protected function onRender(TEventArgs $e)
	{
		parent::onRender($e);
		
	    if($form = $this->getPage()->FindControl('TForm'))
        {
            $config = $this->Config2Json();
            
            $form->AddScript("
                $('#".$this->getClientId()."').slider($config).bind('slidestop', function(e, ui) { $('#".$this->_valueField->getClientId()."').val(ui.value) });
            ");
        }
	}
}