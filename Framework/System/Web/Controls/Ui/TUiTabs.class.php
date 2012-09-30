<?php
/**
 * 
 */
class TUiTabs extends TUiControl
{
	protected $_hidden_field;
	
	protected function onCreate(TEventArgs $e)
	{
		parent::onCreate($e);
		
		TAssetManager::Publish('/Assets/jQuery/ui/jquery-ui-tabs.js');
		
		$this->_hidden_field = new TUiTabs_HiddenField;
		$this->AddControl($this->_hidden_field);
		$this->_hidden_field->RaiseEvent('onCreate');
		$this->_hidden_field->RestoreViewState();
		
		if(isset($_POST[$this->_hidden_field->getClientId()]))
		{
			$this->setActiveTabIndex($_POST[$this->_hidden_field->getClientId()]);
		}
	}
	
	public function setActiveTabIndex($index)
	{
		$this->setViewState('ActiveTabIndex', TVar::toInt($index));
	}
	
	public function setActiveTab(TUiTab $tab)
	{
		$this->setViewState('ActiveTab', $tab->getClientId());
	}
	
	public function getActiveTabIndex()
	{
		return $this->getViewState('ActiveTabIndex', 0);
	}
	
	protected function onRender(TEventArgs $e)
	{
		parent::onRender($e);
		
		if($form = $this->getPage()->FindControl('TForm'))
		{
			$this->_config['selected'] = $this->getActiveTabIndex();
			$this->_hidden_field->Text = $this->getActiveTabIndex();
				
			$config = $this->Config2Json();
			
			$form->AddScript("
				$('#".$this->getClientId()."').tabs($config);
				$('#".$this->getClientId()."').bind('tabsshow', function(e, ui)
					{
						$('#".$this->_hidden_field->getClientId()."').val($(this).tabs('option','selected'));
						$('#".$this->_hidden_field->getClientId()."').val();
					}
				);
			");
			
			if($tabid = $this->getViewState('ActiveTab'))
			{
				$form->AddScript("
				    $('#".$this->getClientId()."').tabs('select', '#{$tabid}');
				");
			}
		}
	}
	
	public function RenderContent()
	{
		echo "<ul>\n";
		foreach($this->_controls as $control)
		{
			if(($control instanceOf TUiTab) && $control->getVisible())
			{
				echo "\t<li><a href=\"#".$control->getClientId().'">'.$control->getLabel()."</a></li>\n";
			}
		}
		echo "</ul>\n";
		
		parent::RenderContent();
	}
	
	public function getTagName()
	{
		return 'div';
	}
	
	public function getAllowChildControls()
	{
		return array('TLiteral', 'TUiTab', 'TUiTabs_HiddenField');
	}
}

class TUiTab extends TPanel
{
	protected $_label;
	
	public function setLabel($text)
	{
		$this->setViewState('Label',$text);
	}
	
	public function getLabel()
	{
		return $this->getViewState('Label');
	}
}

class TUiTabs_HiddenField extends THiddenField {}