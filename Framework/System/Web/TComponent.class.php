<?php
/**
 * 
 */
Engine::Using('System.Object');
Engine::Using('System.Web.Events.EventArgs');

class TComponent extends TObject
{
	private $_callbacks = array();
	
	/**
	 * Raises an event specified by $eventName - optionally you can add event arguments using TEventArgs class (or its sub class)
	 * 
	 * @param $eventName
	 * @param $args
	 * @return unknown_type
	 */
	
	public function RaiseEvent($eventName, TEventArgs $args = null)
	{
		$eventName = strtolower($eventName);
		
		if($args == null) $args = new TEventArgs;
		
		$this->$eventName($args);
		
		if(isset($this->_callbacks[$eventName]) && !empty($this->_callbacks[$eventName]))
		{
			foreach($this->_callbacks[$eventName] as $callback)
			{
				$params = array($this, $args);
				
				if($callback[0] instanceOf TControl)
				{
					$target = $callback[0]->getParentTemplateControl();
					
					while(($target instanceOf TRepeaterRenderer))
					{
						$target = $target->getParentTemplateControl();
					}
				}
				else
				{
					$target = $callback[0];
				}
				
				if(Engine::getMode() == Engine::MODE_DEBUG)
				{
					try
					{	
						$ref = new ReflectionObject($target);
						$ref = $ref->getMethod($callback[1]);
						
						if($ref->isPrivate()) //callback method must not be private
						{
							throw new CoreException('Method '.get_class($target).'::'.$callback[1].'() must be public or protected in order to be a valid callback');
						}
						
						if($ref->isStatic()) //callback method must not be static
						{
							throw new CoreException('Method '.get_class($target).'::'.$callback[1].'() must be non-static or protected in order to be a valid callback');
						}
					}
					catch(ReflectionException $e)
					{
						throw new CoreException('Call to undefined method '.get_class($target).'::'.$callback[1].'()');
					}
				}
				
				call_user_func_array(array($target, $callback[1]), $params);
			}
		}		
	}
	
	public function __set($attribute, $value)
	{
		if(strcasecmp(substr($attribute, 0, 2), 'on') == 0)
		{
			if(!method_exists($this, $attribute))
			{
				throw new CoreException(get_class($this).' does not support event `'.$attribute.'`');
			}
			
			$this->_callbacks[$attribute][] = $value;
		}
	}
	
	public function isPostBack()
	{
		return !empty($_POST);
	}
}
