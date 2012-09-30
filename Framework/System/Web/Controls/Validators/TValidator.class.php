<?php
/**
 * 
 */
abstract class TValidator extends TControl
{
	protected 
		$_display = 'dynamic',
		$_enableClientScript = true,
		$_controlToValidate = '',
		$_controlCssClass = '',
		$_clientValidateFunction = '',
		$_valid = true,
		$_errorMessage = '*',
		$_validationGroup = '',
		$_setFocus = true,
		$_displayMessage = true
	;
	
	protected static $_validators = array();
	
	public function __construct()
	{
		self::$_validators[] = $this;
	}
	
	public function setFocusOnError($setFocus)
	{
		$this->_setFocus = TVar::toBool($setFocus);
	}
	
	public function getFocusOnError()
	{
		return $this->_setFocus;
	}
	
	public function setDisplayMessage($displayMessage)
	{
		$this->_displayMessage = TVar::toBool($displayMessage);
	}
	
	public function getDisplayMessage()
	{
		return $this->_displayMessage;
	}
	
	public function setValidationGroup($validationGroup)
	{
		$this->_validationGroup = $validationGroup;
	}
	
	public function getValidationGroup()
	{
		return $this->_validationGroup;
	}
	
	public function getEnableViewState()
	{
		return false;
	}
	
	public function getEnableClientScript()
	{
		return $this->_enableClientScript;
	}
	
	public function setEnableClientScript($enabled)
	{
		$this->_enableClientScript = TVar::toBool($enabled);
	}
	
	public function setControlToValidate($controlId)
	{
		$this->_controlToValidate = $controlId;
	}
	
	public function getControlToValidate()
	{
		return $this->_controlToValidate;
	}
	
	public function setErrorMessage($message)
	{
		$this->_errorMessage = $message;
	}
	
	public function getErrorMessage()
	{
		return $this->_errorMessage;
	}
	
	public function setControlCssClass($cssClass)
	{
		$this->_controlCssClass = $cssClass;
	}
	
	public function getControlCssClass()
	{
		return $this->_controlCssClass;
	}
	
	public function setClientValidateFunction($function)
	{
		$this->_clientValidateFunction = str_replace("\n", ' ', $function);
	}
	
	public function getClientValidateFunction()
	{
		return $this->_clientValidateFunction;
	}
	
	public function getControlToValidateObject()
	{
		$id = $this->getControlToValidate();
		
		return $this->getParentTemplateControl()->$id;
	}
	
	public function getTagName()
	{
		if($this->_display == 'static')
		{
			return 'div';
		}
		else
		{
			return 'div';//return 'span';
		}
	}
	
	public function RenderContent()
	{
		if($this->_display == 'static' || !$this->getIsValid())
		{
			echo $this->_errorMessage;
		}
	}
	
	public function setDisplay($display)
	{
		if(!in_array($display, array('dynamic','static')))
		{
			throw new InvalidAttributeException($display,'Display');
		}
		
		$this->_display = $display;
	}
	
	protected function setIsValid($valid)
	{
		$this->_valid = $valid;
	}
	
	public function getIsValid()
	{
		return $this->_valid;
	}
	
	public function Validate()
	{
		$e = new TValidatorEventArgs;
		$e->Control = $this->getControlToValidateObject();
		
		$this->raiseEvent('onServerValidate', $e);
		
		if(!$this->_valid)
		{
			$e->Control->addCssClass($this->getControlCssClass());
		}
		else
		{
			$e->Control->removeCssClass($this->getControlCssClass());
		}
		
		return $this->_valid;
	}
	
	protected function onCreate(TEventArgs $e)
	{
		TAssetManager::Publish('/Assets/jQuery/jquery.js');
		TAssetManager::Publish('/Assets/TValidator.js');
	}
	
	protected function onRender(TEventArgs $e)
	{
		if($this->getEnableClientScript() && ($form = $this->getPage()->FindControl('TForm')))
		{
			$setFocus 					= TVar::toInt($this->getFocusOnError());
			$displayMessage 			= TVar::toInt($this->getDisplayMessage());
			$controlToValidate 			= $this->getControlToValidate();
			$clientId 					= $this->getClientId();
			$clientValidateFunction		= preg_match('{^/.*/$}', $this->getClientValidateFunction()) ? $this->getClientValidateFunction() : 'function(control) { '.$this->getClientValidateFunction().' }';
			$errorMessage				= str_replace("'", "\\'", $this->getErrorMessage());
			$controlCssClass			= $this->getControlCssClass();
			$validationGroup			= $this->getValidationGroup();
			
			$form->AddScript("TValidator.add('{$controlToValidate}','{$clientId}',{$clientValidateFunction},'{$errorMessage}','{$controlCssClass}',{$setFocus},{$displayMessage},'{$validationGroup}','{$this->_display}');");
		}
		
		if($this->_display == 'static')
		{
			if($this->getIsValid())
			{
				$this->Style['visibility'] = 'hidden';
			}
			else
			{
				$this->Style['visibility'] = 'visible';
			}
		}
	}
	
	protected function onServerValidate(TValidatorEventArgs $e) {}
}

class TValidatorEventArgs extends TEventArgs
{
	public $Control; 
}