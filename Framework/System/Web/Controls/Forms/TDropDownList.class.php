<?php
/**
 * 
 */
class TDropDownList extends TDataBoundControl
{
	protected $_options = array();
	
	protected function onCreate(TEventArgs $args)
	{
		$name = $this->getViewState('name', $this->getClientId());
		
		$this->setName($name);
	}
	
	public function setDataTextField($field)
	{
		$this->setViewState('DataTextField', $field);
	}
	
	public function getDataTextField()
	{
		return $this->getViewState('DataTextField');
	}
	
	public function setDataValueField($field)
	{
		$this->setViewState('DataValueField', $field);
	}
	
	public function getDataValueField()
	{
		return $this->getViewState('DataValueField');
	}
	
	public function getSelectedIndex()
	{
		return $this->getViewState('selectedIndex', 0);
	}
	
	public function getSelectedValue()
	{
		if(!empty($this->_options) && isset($this->_options[$this->getSelectedIndex()]))
		{
			return $this->_options[$this->getSelectedIndex()]->getValue();
		}
	}
	
	public function getSelectedText()
	{
		if(!empty($this->_options) && isset($this->_options[$this->getSelectedIndex()]))
		{
			return $this->_options[$this->getSelectedIndex()]->getText();
		}
	}
	
	public function setSelectedValue($selected)
	{
		if(empty($this->_options))
		{
			$this->setViewState('selectedValue', $selected);
		}
		else
		{
			$this->setViewState('selectedValue', $selected);
			
			foreach($this->_options as $k => $option)
			{
				if($option->getValue() == $selected)
				{
					$option->setSelected(true);
					$this->setViewState('selectedIndex', $k);
					break;
				}
			}
		}
	}
	
	public function getText()
	{
		if(!empty($this->_options) && isset($this->_options[$this->getSelectedIndex()]))
		{
			return $this->_options[$this->getSelectedIndex()]->getText();
		}
	}
	
	public function setSelectedIndex($index)
	{
		$index = TVar::toInt($index);
		
		foreach($this->_options as $k => $option)
		{
			$option->setSelected($k == $index);
		}
	}
	
	protected function _bind($data, $key)
	{
		$textField = $this->getDataTextField();
		$valueField = $this->getDataValueField();
		
		if($textField != null && $valueField != null && isset($data[$textField]) && isset($data[$valueField]))
		{
			$text = $data[$textField];
			$value = $data[$valueField];
		}
		else
		{
			$text = $data;
			$value = $data;
		}
		
		$item = new TListItem;
		$this->AddControl($item);
		$item->setText($text);
		$item->setValue($value);
		
		$name = $this->getName();
		
		if(substr($name, -2, 2) == '[]')
		{
			$name = substr($name, 0, -2);
			
			$is_array = true;
		}
		else
		{
			$is_array = false;
		}
		
		if(isset($_POST[$name]))
		{
			if($is_array)
			{
			    $expect = isset($_POST[$name][0]) ? $_POST[$name][0] : false;
			}
			else
			{
				$expect = $_POST[$name];
			}
			
			if($value == $expect)
			{
				$item->setSelected(true);
				$this->setViewState('selectedIndex', count($this->_options));
			}
			else
			{
				$item->setSelected(false);
			}
		}
		else
		{
			if($value == $this->getViewState('selectedValue'))
			{
				$item->setSelected(true);
				$this->setViewState('selectedIndex', count($this->_options));
			}
			else
			{
				$item->setSelected(false);
			}
		}
			
		$item->RaiseEvent('onCreate');
		
		$this->_options[] = $item;
	}
	
	public function addOption(TListItem $item)
	{
	    $name = $this->getName();
        
        if(substr($name, -2, 2) == '[]')
        {
            $name = substr($name, 0, -2);
            
            $is_array = true;
        }
        else
        {
            $is_array = false;
        }
        
        if(isset($_POST[$name]))
        {
        	if($item->getValue() == $_POST[$name])
        	{
        		$item->setSelected(true);
                $this->setViewState('selectedIndex', count($this->_options));
                $this->setViewState('selectedValue', $item->getValue());
        	}
        	else
        	{
        		$item->setSelected(false);
        	}
        }

		$this->_options[] = $item;
	}
	
	protected function getName()
	{
		return $this->getViewState('name');
	}
	
	protected function setName($name)
	{
		$this->setAttributeToRender('name', $name);
		$this->setViewState('name', $name);	
	}
	
	public function getTagName()
	{
		return 'select';
	}
	
	public function getAllowChildControls()
	{
		return array('TLiteral', 'TListItem');
	}
}