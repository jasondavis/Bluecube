<?php
/**
 * 
 */
Engine::Using('System.Web.Controls.Control');

class TTemplateControl extends TControl
{
	protected $_template;
	protected $_id;
	protected $_cacheExpires = -1;
	
	private static $_templates = array();
	
	public function __construct($templateString = null, $cache_ident = null)
	{
		$self_class = get_class($this);
		
		if($templateString == null)
		{
			$ref = new ReflectionClass($self_class);
			$filename = $ref->getFileName();
		
			if($this instanceOf TPage)
			{
				$template = dirname(dirname($filename)).DS.$self_class.'.page';
				$cache_key = 'Templates:Pages:'.$self_class;
			}
			else
			{
				$template = dirname($filename).DS.'Templates'.DS.$self_class.'.tpl';
				$cache_key = 'Templates:Controls:'.$self_class;
			}
			
			if(!Engine::file_exists($template)) throw new CoreException('Template file '.$template.' could not be found');
			
			$templateString = file_get_contents($template, LOCK_EX);
		}
		else
		{
			$page_class = ($this instanceOf TPage) ? '' : get_class(TPage::getCurrentPage());
			$cache_ident = $page_class.'__'.$cache_ident;
			
			$cache_key = 'Templates:Dynamic:'.$cache_ident;
			$self_class = $page_class.'__'.$this->_id;
		}
		
		$template_class = $self_class.'_Template';
		
		if(!class_exists($template_class, false))
		{
			if(Engine::getMode() != Engine::MODE_PERFORMANCE || !Engine::IsCached($cache_key))
			{
				Engine::Using('System.Web.Templating.TemplateCompiler');
			
				$compiler = new TTemplateCompiler($templateString, $self_class);
			
				TCache::Write($cache_key, $compiler->GetCode(), 0);
			}
		
			TCache::EvaluateOnce($cache_key);
		}
		
		$this->_template = new $template_class($this);
	}
	
	public function getId()
	{
		return $this->_id;
	}
	
	public function setId($id)
	{
		$this->_id = $id; 
	}
	
	/**
	 * Returns template associated with control
	 * 
	 * @return TControl
	 */
	
	final public function getTemplate()
	{
		return $this->_template;
	}
	
	final public function getControls()
	{
		return $this->_template->getControls();
	}
	
	public function __get($name)
	{
		$getter = "get$name";
		
		if(method_exists($this, $getter))
		{
			return $this->$getter();
		}
		return $this->_template->getControl($name);
	}
	
	public function __set($name, $value)
	{
		if(strcasecmp(substr($name, 0, 2), 'on') == 0)
		{	
			parent::__set($name, array($this, $value));
			
			return;
		}
		
		$setter = "set$name";
		
		if(method_exists($this, $setter))
		{
			$this->$setter($value);
			return;
		}
		
		if($this->_template->getControl($name) instanceOf TControl)
		{
			throw new InvalidOperationException('Access denied to destroy '.get_class($this).'::$'.$name);
		}
	}
	
	public function AddControl(TControl $control)
	{
		$this->_template->AddControl($control);
	}
	
	public function getTagName()
	{
		return null;
	}
	
	public function setCacheExpires($expires)
	{
		$this->_cacheExpires = $expires;
	}
	
	public function Render()
	{
		if(!$this->getVisible()) return;
		
		$is_cacheable = $this->_cacheExpires > -1 && Engine::getMode() == Engine::MODE_PERFORMANCE;
		
		if($is_cacheable)
		{
		    $props = array();
		    
		    $ref = new ReflectionObject($this);
		    
		    foreach($ref->getProperties() as $prop)
		    {
		        if($prop->getDeclaringClass()->getName() == $ref->getName())
		        {
		            $name = $prop->getName();
		            $props[$name] = $this->$name;
		        }
		    }
		    
		    $key = var_export($props, 1);
		    
			$id = $this->getClientId() == '' ? $this->getId() : $this->getClientId();
			$cache_key = 'TemplateControls:'.get_class($this->getPage()).':'.get_class($this).':'.$id.':'.md5($key);
			
			if($content = TCache::Read($cache_key))
			{
				echo $content;
				return;
			}
			else
			{
				ob_start();
			}
		}
		
		$this->RaiseEvent('onRender');
		
		if($this->getVisible())
		{
			$this->_template->Render();
		}
		
		if($is_cacheable)
		{
			$content = ob_get_clean();
			
			TCache::Write($cache_key, $content, $this->_cacheExpires);
			
			echo $content;
		}
	}
}