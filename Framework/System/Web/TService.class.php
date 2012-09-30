<?php
/**
 * 
 */
abstract class TService extends TObject
{
	protected $_cache_expires = -1;
	protected $_output_renderer;
	
	public function __construct()
	{
		/*
		 * In debug mode, we check whether the TService control-flow methods are declared public.
		 * If yes, throw an exception. These methods must not be public because they are called
		 * synchronously by the Framework and must not be callable using request action (all
		 * TService public methods are action methods)
		 */
		if(Engine::getMode() == Engine::MODE_DEBUG)
		{
			$ref = new ReflectionMethod($this, 'Service_Init');
			if($ref->isPublic()) throw new CoreException('Method '.get_class($this).'::Service_Init() must not be public');
			
			$ref = new ReflectionMethod($this, 'Service_Load');
			if($ref->isPublic()) throw new CoreException('Method '.get_class($this).'::Service_Load() must not be public');
			
			$ref = new ReflectionMethod($this, 'Service_Dispose');
			if($ref->isPublic()) throw new CoreException('Method '.get_class($this).'::Service_Dispose() must not be public');
		}
		
		$this->Service_Init();
		
		$action = trim($_GET['action']);
			
		if(Engine::getMode() == Engine::MODE_DEBUG && $action != null)
		{
			try
			{
				$ref = new ReflectionMethod($this, $action);

				if(!$ref->isPublic()) //Action method must be public
				{
					throw new CoreException('Action method '.get_class($this).'::'.$ref->getName().'() must be public');
				}
					
				if($ref->isStatic()) //Action method must not be static
				{
					throw new CoreException('Action method '.get_class($this).'::'.$ref->getName().'() must not be static');
				}
			}
			catch(ReflectionException $e)
			{
				throw new CoreException($e->getMessage());
			}
		}
		
		if($action != null)
		{
		    $ret = $this->$action(new TActionArgs);
		    
		    if($this->_output_renderer)
		    {
		        echo $this->_output_renderer->render($ret);
		    }
		    else
		    {
		        echo $ret;
		    }

		}
		
		$this->Service_Load();
		
		$this->Service_Dispose();
	}
	
	public function setOutputRenderer(IOutputRenderer $renderer)
	{
	    $this->_output_renderer = $renderer;
	}
	
	public function getOutputRenderer()
	{
	    return $this->_output_renderer;
	}
	
	public function setCacheExpires($expires)
	{
		$this->_cache_expires = (int) $expires;
	}
	
	public function getCacheExpires()
	{
		return $this->_cache_expires;
	}
	
	protected function Service_Init() {}
	protected function Service_Load() {}
	protected function Service_Dispose() {}
}
define('__DONT_SHOW_DEBUG_INFO__', 1);