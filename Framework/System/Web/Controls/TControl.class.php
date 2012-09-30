<?php
/**
 * 
 */
Engine::Using('System.Lib.Var');
Engine::Using('System.Web.Viewstate.Viewstate');
Engine::Using('System.Web.Component');
Engine::Using('System.Web.Style');

class TControl extends TComponent
{
	protected $_controls = array();
	protected $_parent;
	protected $_clientId;
	protected $_id;
	protected $_attributes = array();
	protected $_parentTemplateControl;
	protected $_enableViewState = true;
	protected $_style;
	
	public function getPage()
	{
		return TPage::getCurrentPage();
	}
	
	public function __set($attribute, $value)
	{
		if(strcasecmp(substr($attribute, 0, 2), 'on') == 0)
		{
			if(!is_array($value)) $value = array($this, $value); //may already be array if called from TTemplateControl
			
			parent::__set($attribute, $value);
			
			return;
		}
		$setter = 'set'.$attribute;
		
		if(method_exists($this, $setter))
		{
			$this->$setter($value);
		}
		else
		{
			$attribute = strtolower($attribute);
			$this->setViewState($attribute, $value);
			$this->setAttributeToRender($attribute, $value);
		}
	}
	
	public function RaisePostBackEvent()
	{
		
	}
	
	public function __get($attribute)
	{
		$getter = 'get'.$attribute;
		
		if(method_exists($this, $getter))
		{
			return $this->$getter();
		}
		else
		{
			$attribute = strtolower($attribute);
			return $this->getViewState($attribute);
		}
	}
	
	/*
	 * Control hierarchy functions
	 */
	
	/**
	 * Adds given control to the list of child controls
	 * 
	 * @param $control TControl
	 * @return unknown_type
	 */
	
	public function AddControl(TControl $control)
	{
		if(Engine::getMode() == Engine::MODE_DEBUG) //in debug mode we check whether $control can be a child of $this
		{
			$allow = $this->getAllowChildControls();
		
			if(is_array($allow))
			{
				if(!in_array(get_class($control), $allow))
				{
					throw new CoreException('Control '.get_class($this).' does not allow '.get_class($control).' as a child, allowed childs: '.implode(', ', $allow));
				}		
			}
			else if(!$allow)
			{
				throw new CoreException('Control '.get_class($this).' does not allow child controls');
			}
		}
		
		if(!$control->getId())
		{
			$control->setId($this->getId().'_ctl'.count($this->_controls));
		}
		
		$this->_controls[$control->getId()] = $control;
		$control->SetParent($this);
	}
	
	/**
	 * Finds the first control that is an instance of $className
	 * Returns false if no control is found
	 * 
	 * @param $className
	 * @return TControl
	 */
	
	public function FindControl($className)
	{
		foreach($this->_controls as $ctl)
		{
			if($ctl instanceOf $className) return $ctl;
		}
		
		return false;
	}
	
	/*
	 * ViewState functions
	 */
	
	public function setEnableViewState($enable)
	{
		$this->_enableViewState = TVar::toBool($enable);
	}
	
	public function getEnableViewState()
	{
		return $this->_enableViewState;
	}
	
	/**
	 * Saves given $value under $attribute in viewstate
	 * 
	 * @param $attribute string
	 * @param $value mixed
	 * @return unknown_type
	 */
	
	public function setViewState($attribute, $value)
	{
		TViewstate::Save($this, $attribute, $value);
	}
	
	/**
	 * Returns value of given $attribute from viewstate
	 * 
	 * @param $attribute string
	 * @param $default mixed
	 * @return mixed
	 */
	
	public function getViewState($attribute, $default = null)
	{
		return TViewstate::Read($this, $attribute, $default);
	}
	
	/**
	 * Restores viewstate of control - this method should be never used by the user
	 * 
	 * @return unknown_type
	 */
	
	public function RestoreViewState()
	{
		$this->RaiseEvent('onRestoreState');
		TViewstate::Restore($this);
		$this->RaiseEvent('onRestoreStateComplete');
	}
	
	/*
	 * Setters & getters
	 */
	
	/**
	 * Sets the client id - client id is the 'id' attribute rendered in the control's tag
	 * 
	 * @param $client_id
	 * @return unknown_type
	 */
	
	public function setClientId($client_id)
	{
		$this->_clientId = $client_id;
		$this->setAttributeToRender('id', $client_id);
	}
	
	/**
	 * Returns the client id - client id is the 'id' attribute rendered in the control's tag
	 * 
	 * @return string
	 */
	
	public function getClientId()
	{
		return $this->_clientId;
	}
	
	/**
	 * Sets the control's ID
	 * 
	 * @param $id
	 * @return unknown_type
	 */
	
	public function setId($id)
	{
		if(!$this->_id) $this->_id = $id;
		$this->setClientId($id);
	}
	
	/**
	 * Returns the control's ID
	 * 
	 * @param $id
	 * @return string
	 */
	
	public function getId()
	{
		return $this->_id;
	}
	
	/**
	 * Sets the parent control of this control - only framework developers should use this method.
	 * 
	 * @param $parent
	 * @return unknown_type
	 */
	
	public function setParent(TControl $parent)
	{
		$this->_parent = $parent;
		$this->_parentTemplateControl = $parent->getParentTemplateControl();
	}
	
	/**
	 * Returns the TTemplateControl which template contains this control
	 * 
	 * @return TTemplateControl
	 */
	
	public function &getParentTemplateControl()
	{
		return $this->_parentTemplateControl;
	}
	
	/**
	 * Returns the parent control
	 * 
	 * @return TControl
	 */
	
	public function getParent()
	{
		return $this->_parent;
	}
	
	/**
	 * Returns the controls tag which will be rendered as the template is rendered
	 * 
	 * @return unknown_type
	 */
	
	public function getTagName()
	{
		return 'span';
	}
	
	/**
	 * Returns the list of child controls
	 * 
	 * @return array
	 */
	
	public function getControls()
	{
		return $this->_controls;
	}
	
	public function getHasEndTag()
	{
		return true;
	}
	
	public function getAllowChildControls()
	{
		return true;
	}
	
	public function setText($text)
	{
		/*if($this->getAllowChildControls())
		{
			$literal = new TLiteral;
			$literal->setText($text);
			
			$this->_controls = array($literal);
		}*/
		$this->setViewState('text', $text);
	}
	
	public function getText()
	{
		if($this->getAllowChildControls() && count($this->_controls) == 1)
		{
			$ctls = array_values($this->_controls);
			
			if($ctls[0] instanceOf TLiteral)
			{
				return $ctls[0]->getText();
			}
		}
		
		return $this->getViewState('text','');
	}
	
	public function setVisible($visibility)
	{
		$visibility = TVar::toBool($visibility);
		
		$this->setViewState('visible', $visibility);
		
		if($visibility)
		{
			$this->RaiseEvent('onShow');
		}
		else
		{
			$this->RaiseEvent('onHide');
		}
	}
	
	public function getVisible()
	{
		return $this->getViewState('visible', true);
	}
	
	public function setStyle($style)
	{
		if($style instanceOf TStyle)
		{
			$this->_style = $style;
		}
		else
		{
			$this->_style = new TStyle($style);
		}
		
		$this->setAttributeToRender('style', $this->_style);
		$this->setViewState('style', $this->_style);
	}
	
	public function getStyle()
	{
		if(!$this->_style)
		{
			$this->setStyle('');
		}
		
		return $this->getViewState('style');
	}
	
	/*
	 * Misc functions 
	 */
	
	public function setAttributeToRender($attribute, $value)
	{
		$this->_attributes[$attribute] = $value;
	}
	
	public function Show()
	{
		if(!$this->getVisible())
		{
			$this->setVisible(true);
		}
	}
	
	public function Hide()
	{
		if($this->getVisible())
		{
			$this->setVisible(false);
		}
	}
	
	/*
	 * Rendering functions
	 */
	
	/**
	 * Renders the control
	 * 
	 * @return unknown_type
	 */
	
	public function Render()
	{
		if(!$this->getVisible()) return;
		
		$this->RaiseEvent('onRender');
		
		$hasEndTag = $this->getHasEndTag();
		
		if($tag = $this->getTagName())
		{
			echo "<$tag";
			$this->RenderAttributes();
			echo ($hasEndTag) ? '>' : ' />';
		}
		
		$this->RenderContent();
		
		if($hasEndTag && $tag)
		{
			echo "</$tag>";
		}
		
		$this->RaiseEvent('onRenderComplete');
	}
	
	/**
	 * Renders the list of attributes associated to this control
	 * 
	 * @return unknown_type
	 */
	
	public function RenderAttributes()
	{
		if(!empty($this->_attributes))
		{
			foreach($this->_attributes as $name => $value)
			{
				if($value === null || ($name == 'style' && $value == '')) continue;
				
				echo ' '.$name.'="'.htmlspecialchars((string) $value).'"';
			}
		}
	}
	
	public function setClass($class)
	{
		$this->setCssClass($class);
	}
	
	public function getClass()
	{
		return $this->getCssClass();
	}
	
	public function setCssClass($class)
	{
		if(is_array($class)) $class = implode(' ', $class);
		$this->setViewState('cssClass', explode(' ', trim($class)));
		$this->setAttributeToRender('class', trim($class));
	}
	
	public function getCssClass()
	{
		return implode(' ', $this->getViewState('cssClass', array()));
	}
	
	public function addCssClass($class)
	{
		$classes = explode(' ', $this->getCssClass());
		if(!in_array($class, $classes))
		{
			$classes[] = $class;
			$this->setCssClass(implode(' ', $classes));
		}
	}
	
    public function removeCssClass($class)
    {
    	$classes = explode(' ', $this->getCssClass());
    	
        if($pos = array_search($class, $classes))
        {
            unset($classes[$pos]);
            $this->setCssClass(implode(' ', $classes));
        }
    }
	
	/**
	 * Renders the content of the control
	 * 
	 * @return unknown_type
	 */
	
	public function RenderContent()
	{
		$text = $this->getViewState('text','');
		
		if($this->getAllowChildControls())
		{
			if(!empty($this->_controls) && $text == '')
			{
				foreach($this->_controls as $control)
				{
					$control->Render();
				}
			}
			else
			{
				echo $text;
			}
		}
	}
	
	/*
	 * Events
	 */
	
	protected function onCreate(TEventArgs $args) {}
	protected function onRestoreState(TEventArgs $args) {}
	protected function onRestoreStateComplete(TEventArgs $args) {}
	protected function onRender(TEventArgs $args) {}
	protected function onRenderComplete(TEventArgs $args) {}
	protected function onShow(TEventArgs $args) {}
	protected function onHide(TEventArgs $args) {}
}