<?php
/**
 * TAutoloader class
 * 
 * This class is used for SPL's autoload feature to
 * load automatically required class.
 * 
 * 
 *
 */
class TAutoloader extends TObject
{
	private static $cache = array();
	private static $mode = 0;
	
	const MODE_SYSTEM = 0;
	
	/**
	 * Automatically loads requested class using cache (always) or
	 * filesystem (if engine's mode is not MODE_PERFORMANCE)
	 * 
	 * @param $class
	 * @return void
	 */
	
	public function Load($class)
	{
		switch(self::$mode)
		{
			case self::MODE_SYSTEM:
				$this->LoadSystemClass($class);
			break;
		}
	}
	
	public static function SetMode($mode)
	{
		self::$mode = $mode;
	}
	
	private function LoadSystemClass($class)
	{
		$loaded = false;
		
		if($this->LoadFromCache($class))
		{
			$loaded = true;
		}
		
		if(!$loaded)
		{
			$loaded = $this->LoadFromFilesystem($class);
		}
		
		if(!$loaded)
		{
			$class = preg_replace('{[^a-zA-Z0-9_]}', '', $class);
			
			if($class != null)
			{
				eval("class $class {}"); //PHP need this hack to throw the following exception
				throw new CoreException('Class '.$class.' not found');
			}
			throw new CoreException('Invalid class name');
		}
	}
	
	/**
	 * Loads requested class from cache. This method
	 * is called automatically by Load(). Returns true
	 * if class path is cached and class file loaded
	 * successfuly. Returns false if failed.
	 * 
	 * @param $class
	 * @return boolean
	 */
	
	private function LoadFromCache($class)
	{
		if(empty(self::$cache))
		{
			if($path = Engine::GetCachePath('ClassIndex'))
			{
			    include $path;
			} 
		}
				
		$class = strtolower($class);
		
		if(!isset(self::$cache[$class])) return false;
				
		if(!file_exists(self::$cache[$class])) return false;
		
		include_once self::$cache[$class];
		
		return true;
	}
	
	/**
	 * Loads requested class from the filesystem. Searches recursively the 
	 * Framework's system directory for needed class file. Returns true
	 * on success or false on failure.
	 * 
	 * @param $class
	 * @return boolean
	 */
	
	private function LoadFromFilesystem($class)
	{
		static $iterators = array();
		
		if(empty($iterators))
		{
		    $iterators[] = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(SYSTEM_DIR));
		    if(defined('SITE_ROOT'))
            {
                if(is_dir(SITE_ROOT.DS.'Pages'.DS.'CodeBehind')) $iterators[] = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(SITE_ROOT.DS.'Pages'.DS.'CodeBehind'));
                if(is_dir(SITE_ROOT.DS.'Services')) $iterators[] = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(SITE_ROOT.DS.'Services'));
                if(is_dir(SITE_ROOT.DS.'Runtime')) $iterators[] = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(SITE_ROOT.DS.'Runtime'));
            }
            if(is_dir(ROOT_DIR.DS.'Runtime')) $iterators[] = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(ROOT_DIR.DS.'Runtime'));	
		}
		
		$class = strtolower($class);
		$cache = "<?php\nself::\$cache = array(\n";
		$include = false;
		$included = false;
		
		foreach($iterators as $iterator)
		{
			foreach($iterator as $file)
			{
				$filename = basename($file);
				$filename = strtolower(substr($filename, 0, strpos($filename, '.')));
			
				if(preg_match('{\.class\.php$}', $file))
				{
					$cache .= "\t'$filename' => '$file',\n";
			
					if($filename == $class)
					{
						$include = $file;
					}
				}
			}
		
			if($include && !$included)
			{
				$included = true;
				include_once $include;
			}
		}
		
		$cache .= ");";
		
		Engine::WriteCache($cache,'ClassIndex');
		
		return $included;
	}
}

spl_autoload_register(array(new TAutoloader, 'Load'));
