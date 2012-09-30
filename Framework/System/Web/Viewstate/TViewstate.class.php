<?php
/**
 * 
 */
class TViewstate extends TObject
{
	private static $_data = array(); //array which will be saved (used by controls that have enabled viewstate)
	private static $_unsave = array(); //array which wll not be saved (used by controls that have disabled viewstate)
	private static $_prefix = '';
	private static $_prefix_stack = array();
	private static $_controller;
	
	public static function setPrefix($prefix)
	{
		if($prefix != '')
		{
			self::$_prefix_stack[] = $prefix.':';
			self::$_prefix = $prefix.':';
		}
		else
		{
			array_pop(self::$_prefix_stack);
			self::$_prefix = empty(self::$_prefix_stack) ? '' : end(self::$_prefix_stack);
		}
	}
	
	public static function Save(TControl $control, $name, $value)
	{
		if($control instanceOf TPage)
		{
			$id = 'page:'.get_class($control);
		}
		else
		{
			$id = $control->getId();
		}
		
		$name = strtolower($name);
		
		if($control->getEnableViewState() || ($control instanceOf TPage))
		{
			self::$_data[self::$_prefix.$id][$name] = $value;
		}
		else
		{
			self::$_unsave[self::$_prefix.$id][$name] = $value;
		}
	}
	
	private static function __initialize()
	{
		if(!self::$_controller)
		{
			$controller = Engine::GetConfig('viewstate/controller[@enabled="true"]', Engine::SITECONFIG);
			
			if(Engine::getMode() == Engine::MODE_DEBUG && empty($controller))
			{
				throw new CoreException('Viewstate controller not configured');
			}
			
			if(Engine::getMode() == Engine::MODE_DEBUG && count($controller) > 1)
			{
				throw new CoreException('Only one viewstate controller can be enabled');
			}
			
			$opts = Engine::GetConfig('viewstate/controller[@enabled="true" and @class="'.$controller[0]['class'].'"]/option', Engine::SITECONFIG);
			$options = array();

			foreach($opts as $option)
			{
				$options[$option['name']] = $option['value'];
			}
			
			self::$_controller = new $controller[0]['class']($options, isset($_POST['__VIEWSTATE']) ? trim($_POST['__VIEWSTATE']) : null);
			
			if(Engine::getMode() == Engine::MODE_DEBUG && !(self::$_controller instanceOf IViewstateController))
			{
				throw new Exception("{$controller[0]['class']} must implement IViewstateController interface");
			}
		}
	}
	
	public static function SerializeData()
	{
	    $abort = ignore_user_abort(true);
	    
		self::__initialize();
		
		$ret =  self::$_controller->write(self::$_data);
		
		ignore_user_abort($abort);
		
		return $ret;
	}
	
	/**
	 * Unserializes and restores ViewState data after postback
	 * 
	 * @return void
	 */
	
	private static function UnserializeData()
	{
		self::__initialize();
		
		static $unserialized_after_post;
		
		if(!$unserialized_after_post)
		{
			try
			{
				$unserialized_after_post = self::$_controller->read();
			}
			catch(Exception $e)
			{
				return array();
			}
		}
		
		return $unserialized_after_post ? $unserialized_after_post : self::$_data;
	}
	
	public static function Read(TControl $control, $name, $default = null)
	{
		if($control instanceOf TPage)
		{
			$id = 'page:'.get_class($control);
		}
		else
		{
			$id = $control->getId();
		}
		
		
		$name = strtolower($name);
		
		if($control->getEnableViewState() || ($control instanceOf TPage))
		{
			if(isset(self::$_data[self::$_prefix.$id][$name]))
			{
				return self::$_data[self::$_prefix.$id][$name];
			}
		}
		else
		{
			if(isset(self::$_unsave[self::$_prefix.$id][$name]))
			{
				return self::$_unsave[self::$_prefix.$id][$name];
			}
		}
		
		return $default;
	}
	
	/**
	 * Restores ViewState of given control
	 * 
	 * @param TControl $control
	 * @return void
	 */
	
	public static function Restore(TControl $control)
	{
		if(!isset($_POST['__VIEWSTATE'])) return;
		
		$data = self::UnserializeData();
		
		$id = $control->getClientId();
		
		if(isset($data[self::$_prefix.$id]))
		{
			foreach($data[self::$_prefix.$id] as $k => $v)
			{
				if($k == 'id') continue;
				
				$control->$k = $v;
			}
		}
	}
}
