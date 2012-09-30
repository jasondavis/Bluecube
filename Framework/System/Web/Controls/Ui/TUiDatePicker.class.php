<?php
/**
 * 
 */
class TUiDatePicker extends TUiControl
{
	public function getTagName()
	{
		return 'input';
	}
	
	public function getAllowChildControls()
	{
		return false;
	}
	
	public function getHasEndTag()
	{
		return false;
	}
	
	public function setDate($date)
	{
		$this->setViewState('date', $date);
	}
	
	public function getDate()
	{
		return $this->getViewState('date');
	}
	
	public function setFormat($format)
	{
		$this->setViewState('format',$format);
	}
	
	public function getFormat()
	{
		return $this->getViewState('format','dd/mm/yyyy');
	}
	
	protected function getName()
	{
		return $this->getViewState('name');
	}
	
	public function setChangeYear($bool)
	{
		$this->setViewState('changeYear', TVar::toBool($bool));
	}
	
	public function getChangeYear()
	{
		return $this->getViewState('changeYear', false);
	}
	
    public function setChangeMonth($bool)
    {
        $this->setViewState('changeMonth', TVar::toBool($bool));
    }
    
    public function getChangeMonth()
    {
        return $this->getViewState('changeMonth', false);
    }
	
	protected function setName($name)
	{
		$this->setAttributeToRender('name', $name);
		$this->setViewState('name', $name);	
	}
	
	protected function onCreate(TEventArgs $e)
	{
		$name = $this->getViewState('name', $this->getClientId());
		$this->setName($name);
		
		if(!empty($_POST))
		{
			$this->setText(isset($_POST[$name]) ? $_POST[$name] : null);
		}
		
		parent::onCreate($e);
		
		TAssetManager::Publish('/Assets/jQuery/ui/jquery-ui-datepicker.js');
	}
	
	protected function onRender(TEventArgs $e)
	{
		parent::onRender($e);
		
		if($form = $this->getPage()->FindControl('TForm'))
		{
			$this->_config['dateFormat'] = $this->getFormat();
			$this->_config['changeMonth'] = $this->getChangeMonth();
			$this->_config['changeYear'] = $this->getChangeYear();
			$config = $this->Config2Json();
			
			$form->AddScript("
				$('#".$this->getClientId()."').datepicker($config);
			");
		}
		$this->setAttributeToRender('value', $this->getText());
	}
}