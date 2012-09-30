<?php
/**
 * 
 */
Engine::Using('System.Web.Controls.TemplateControl');

abstract class TPage extends TTemplateControl
{
	private $_start_lifecycle = array(
		'Page_Init',
		'Raise_Action',
		'Raise_PostBackEvents',
		'Page_Load'
	);
	
	private $_end_lifecycle = array(
		'Render',
		'Page_Dispose'
	);
	
	private $_cache_expires = -1; //-1 - no cache, 0 - cache forever, > 0 - cache for given seconds
	
	private static $_all_controls = array();
	
	private static $_current_page; //contains page that is currently entering next lifecycle step
	
	public function __construct()
	{
		/*
		 * In debug mode, we check whether the TPage control-flow methods are declared public.
		 * If yes, throw an exception. These methods must not be public because they are called
		 * synchronously by the Framework and must not be callable using request action (all
		 * TPage public methods are action methods)
		 */
		if(Engine::getMode() == Engine::MODE_DEBUG)
		{
			$ref = new ReflectionMethod($this, 'Page_Init');
			if($ref->isPublic()) throw new CoreException('Method '.get_class($this).'::Page_Init() must not be public');
			
			$ref = new ReflectionMethod($this, 'Page_Load');
			if($ref->isPublic()) throw new CoreException('Method '.get_class($this).'::Page_Load() must not be public');
			
			$ref = new ReflectionMethod($this, 'Page_Dispose');
			if($ref->isPublic()) throw new CoreException('Method '.get_class($this).'::Page_Dispose() must not be public');
		}
		
		self::$_current_page = $this;
		
		parent::__construct();
	}
	
	final public static function getCurrentPage()
	{
		return self::$_current_page;
	}
	
	/**
	 * Sets the title of the page. Requires Head control to be present on the template.
	 * 
	 * @param $title string
	 * @return unknown_type
	 */
	
	final public function setTitle($title)
	{
		if(!($head = $this->FindControl('THead'))) throw new CoreException('Head control could not be found');
		
		$head->setTitle($title);
	}
	
	/**
	 * Returns the title of the page. Requires Head control to be present on the template.
	 * 
	 * @return string
	 */
	
	final public function getTitle()
	{
		if(!($head = $this->FindControl('THead'))) throw new CoreException('Head control could not be found');
		
		return $head->getTitle();
	}
	
	/**
	 * Links given javascript file
	 * 
	 * @param $url 
	 */
	
	final public function LinkScript($url)
	{
		if(!($head = $this->FindControl('THead'))) throw new CoreException('Head control could not be found');
		
		$head->LinkScript($url);
	}
	
	/**
	 * Links given CSS stylesheet file
	 * 
	 * @param $url 
	 */
	
	final public function LinkCss($url)
	{
		if(!($head = $this->FindControl('THead'))) throw new CoreException('Head control could not be found');
		
		$head->LinkCss($url);
	}
	
	/**
	 * Sets the custom meta tag of the page. Requires Head control to be present on the template.
	 * 
	 * @param $name string
	 * @param $content string
	 * @return unknown_type
	 */
	
	final public function setMeta($name, $content)
	{
		if(!($head = $this->FindControl('THead'))) throw new CoreException('Head control could not be found');
		
		$head->setMeta($name, $content);
	}
	
	/**
	 * Returns the custom meta tag content. Requires Head control to be present on the template.
	 * 
	 * @param $name string
	 * @return string
	 */
	
	final public function getMeta($name)
	{
		if(!($head = $this->FindControl('THead'))) throw new CoreException('Head control could not be found');
		
		return $head->getMeta($name);
	}
	
	/**
	 * Sets the custom http-equiv meta tag content. Requires Head control to be present on the template.
	 * 
	 * @param $name string
	 * @param $content string
	 * @return unknown_type
	 */
	
	final public function setMetaHttpEquiv($name, $content)
	{
		if(!($head = $this->FindControl('THead'))) throw new CoreException('Head control could not be found');
		
		$head->setMetaHttpEquiv($name, $content);
	}
	
	/**
	 * Returns the custom http-equiv meta tag content. Requires Head control to be present on the template.
	 * 
	 * @param $name string
	 * @return string
	 */
	
	final public function getMetaHttpEquiv($name)
	{
		if(!($head = $this->FindControl('THead'))) throw new CoreException('Head control could not be found');
		
		return $head->getMetaHttpEquiv($name);
	}
	
	/**
	 * Sets the meta description tag. Requires Head control to be present on the template.
	 * 
	 * @param $description string
	 * @return unknown_type
	 */
	
	final public function setDescription($description)
	{
		$this->setMeta('description', $description);
	}
	
	/**
	 * Returns the meta description tag content. Requires Head control to be present on the template.
	 * 
	 * @return string
	 */
	
	final public function getDescription()
	{
		return $this->getMeta('description');
	}
	
	/**
	 * Sets the meta keywords tag. Requires Head control to be present on the template.
	 * 
	 * @param $keywords string
	 * @return unknown_type
	 */
	
	final public function setKeywords($keywords)
	{
		$this->setMeta('keywords', $keywords);	
	}
	
	/**
	 * Returns the meta keywords tag content. Requires Head control to be present on the template.
	 * 
	 * @return string
	 */
	
	final public function getKeywords()
	{
		return $this->getMeta('keywords');	
	}
	
	/**
	 * Sets the meta robots tag. Requires Head control to be present on the template.
	 * 
	 * @param $robots string
	 * @return unknown_type
	 */
	
	final public function setRobots($robots)
	{
		$this->setMeta('robots', $robots);	
	}
	
	/**
	 * Returns the meta robots tag content. Requires Head control to be present on the template.
	 * 
	 * @return string
	 */
	
	final public function getRobots()
	{
		return $this->getMeta('robots');	
	}
	
	/**
	 * Sets the meta author tag. Requires Head control to be present on the template.
	 * 
	 * @param $author string
	 * @return unknown_type
	 */
	
	final public function setAuthor($author)
	{
		$this->setMeta('author', $author);	
	}
	
	/**
	 * Returns the meta author tag. Requires Head control to be present on the template.
	 * 
	 * @return string
	 */
	
	final public function getAuthor()
	{
		return $this->getMeta('author');	
	}
	
	/**
	 * Sets cache settings for this page - setting argument to 0 will cache this page forever, setting to -1 (default) will turn the cache off, setting to other number will save the page for $expires seconds. 
	 * 
	 * @param $expires int
	 * @return unknown_type
	 */
	
	final public function setCacheExpires($expires)
	{
		$this->_cache_expires = $expires;
	}
	
	/**
	 * Returns number of seconds which determines how long this page should be cached
	 * 
	 * @return int
	 */
	
	final public function getCacheExpires()
	{
		return $this->_cache_expires;
	}
	
	/**
	 * Finds the first control on the page which is an instance of given class
	 * or false on failure
	 * 
	 * @param $className string
	 * @return TControl
	 */
	
	final public function FindControl($className)
	{
		foreach(self::$_all_controls as $control)
		{
			if($control instanceOf $className) return $control;
		}
		
		return false;
	}
	
	final public static function getControl($id)
	{
		if(!isset(self::$_all_controls[$id])) throw new CoreException("Control '$id' does not exist");
		
		return self::$_all_controls[$id];
	}
	
	final public function __get($id)
	{
		$getter = "get$id";
		
		if(method_exists($this, $getter))
		{
			return $this->$getter();
		}
		
		return self::getControl($id);
	}
	
	final public function __set($id, $value)
	{
		if(strcasecmp(substr($id, 0, 2), 'on') == 0)
		{
			if(!is_array($value)) $value = array($this, $value);
		}
		
		if(isset(self::$_all_controls[$id])) throw new CoreException ("Control '$id' can not be destroyed");
		
		parent::__set($id, $value);
	}
	
	final public function getHasNextStartStep()
	{
		return count($this->_start_lifecycle) > 0;
	}
	
	final public function getHasNextEndStep()
	{
		return count($this->_end_lifecycle) > 0;
	}
	
	/**
	 * Enters the page into next start lifecycle. This method is used
	 * by the framework and should never be used by the user.
	 */
	
	final public function EnterNextStartStep()
	{
		if(count($this->_start_lifecycle) == 0) return false;
		
		self::$_current_page = $this;
		
		$step = array_shift($this->_start_lifecycle);
		
		switch($step)
		{
			case 'Page_Init':
				/*
				 * Register all page controls in global scope so the top-level page can have access to them
				 */
				foreach($this->getTemplate()->getControls() as $id => $control)
				{
					if(method_exists($this,"set$id") || method_exists($this, "get$id"))
					{
						throw new CoreException("Control '".get_class($this)."::$id' cannot be registered, as its ID is in conflict with one of ".get_class($this)."'s setters or getters");
					}
					
					if(isset(self::$_all_controls[$id]))
					{
						throw new CoreException("Page '".get_class($this)."' tries to register control with ID '$id' which has already been registered");
					}
			
					self::$_all_controls[$id] = $control; //everything fine, register it
				}
				
				$css = 'Styles/'.get_class($this).'.css';
				$script = 'Scripts/'.get_class($this).'.js';
				
				if(file_exists($css))
				{
					$this->LinkCss('/'.$css);
				}
				
				$this->getTemplate()->setInitialProperties();
				
				$ref = new ReflectionMethod($this, 'Page_Init');
				
				if($ref->getDeclaringClass()->getName() == get_class($this))
				{
					$this->Page_Init(new TActionArgs);
				}
				
				if(file_exists($script))
				{
					$this->LinkScript('/'.$script);
				}
			break;
			case 'Raise_Action':
				/*
				 * Action is getting executed ONLY on requested page, not in parent pages
				 */
				
				if(isset($_GET['action']) && get_class($this) == $_GET['page'])
				{
					$action = trim($_GET['action']);
			
					if($action != null)
					{
						try
						{
							$ref = new ReflectionMethod($this, $action);
							
							if(Engine::getMode() == Engine::MODE_DEBUG)
							{
								if(!$ref->isPublic()) //Action method must be public
								{
									throw new CoreException('Action method '.get_class($this).'::'.$ref->getName().'() must be public');
								}
					
								if($ref->isStatic()) //Action method must not be static
								{
									throw new CoreException('Action method '.get_class($this).'::'.$ref->getName().'() must not be static');
								}
							}

							/*
							 * Do not execute inherited actions. This is a security reason
							 */
							
							if($ref->getDeclaringClass()->getName() == get_class($this))
							{
								$this->$action(new TActionArgs);
							}
							else //if($ref->getDeclaringClass()->getName() != 'TPage') //Don't throw exception if tried to call some public method from TPage class
							{
								throw new CoreException('Access to call inherited action is denied. Implement method '.get_class($this).'::'.$ref->getName().'() and call its parent implementation instead');
							}
						}
						catch(ReflectionException $e)
						{
							throw new CoreException($e->getMessage());
						}
					}
				}
				
			break;
			case 'Raise_PostBackEvents':
				$this->getTemplate()->RaisePostBackEvents();
			break;
			case 'Page_Load':
				$ref = new ReflectionMethod($this, 'Page_Load');
				
				if($ref->getDeclaringClass()->getName() == get_class($this))
				{
					$this->Page_Load(new TActionArgs);
				}
			break;
		}
		
		return true;
	}
	
	/**
	 * Enters the page into next end lifecycle. This method is used
	 * by the framework and should never be used by the user.
	 */
	
	final public function EnterNextEndStep()
	{
		if(count($this->_end_lifecycle) == 0) return false;
		
		self::$_current_page = $this;
		
		$step = array_shift($this->_end_lifecycle);
		
		switch($step)
		{
			case 'Render':	
				$this->Render();
			break;
			case 'Page_Dispose':
				$ref = new ReflectionMethod($this, 'Page_Dispose');
				
				if($ref->getDeclaringClass()->getName() == get_class($this))
				{
					$this->Page_Dispose(new TActionArgs);
				}
			break;
		}
		
		return true;
	}
	
	final public function getPage()
	{
		return $this;
	}
	
	protected function Page_Init() { }
	protected function Page_Load() { }
	protected function Page_Dispose() { }
}