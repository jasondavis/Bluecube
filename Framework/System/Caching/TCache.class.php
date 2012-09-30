<?php
class TCache extends TObject
{
	const ANY = 1;
	const ALL = 2;
	const NONE = 3;
	
	const OPT_GC_PROBABILITY		= 'gc-probability';
	const OPT_GC_DIVISOR 			= 'gc-divisor';
	const OPT_RUNTIME_MEMORY_CACHE	= 'runtime-memory-cache';

	protected static $_controllers = array();
	protected static $_options = array(
		self::OPT_GC_PROBABILITY 		=> 1,
		self::OPT_GC_DIVISOR			=> 100,
		self::OPT_RUNTIME_MEMORY_CACHE	=> true
	);
	
	protected static $_runtime_cache = array();

	protected static function __initialize()
	{
		if(!empty(self::$_controllers))
		{
			return;
		}

		$controllers = Engine::GetConfig('caching/server-side/controller[@enabled="true"]', Engine::SITECONFIG);
		$config = Engine::GetConfig('caching/server-side/option', Engine::SITECONFIG);
		
		foreach($config as $opt)
		{
			self::$_options[$opt['name']] = $opt['value'];
		}

		foreach($controllers as $controller_class)
		{
			$opts = Engine::GetConfig('caching/server-side/controller[@enabled="true" and @class="'.$controller_class['class'].'"]/option', Engine::SITECONFIG);
			$options = array();

			foreach($opts as $option)
			{
				$options[$option['name']] = $option['value'];
			}

			$controller = new $controller_class['class'](array_merge(self::$_options, $options));

			if(!($controller instanceOf ICacheController))
			{
				throw new InvalidOperationException("$controller_class must implement ICacheController interface");
			}

			self::$_controllers[] = $controller;
		}
		
		if(rand(self::$_options[self::OPT_GC_PROBABILITY], self::$_options[self::OPT_GC_DIVISOR]) == self::$_options[self::OPT_GC_PROBABILITY])
		{
		    $abort = ignore_user_abort(true);
		    
			foreach(self::$_controllers as $controller)
			{
				$controller->CleanExpired();
			}
			
			ignore_user_abort($abort);
		}
	}
	
	public static function getEnabled()
	{
		return !empty(self::$_controllers);
	}

	public static function WriteTagged(array $tags, $content, $expires = 0)
	{
		$content_key = 'Tagged:Contents:'.md5(var_export($tags, true));

		self::Write($content_key, array('tags'=> $tags, 'content' => $content), $expires);

		foreach($tags as $tag)
		{
			$tag_key = 'Tagged:Tags:'.$tag;

			if(!($tags_arr = self::Read($tag_key))) $tags_arr = array();

			if(!in_array($content_key, $tags_arr)) $tags_arr[] = $content_key;

			self::Write($tag_key, $tags_arr);
		}
	}

	public static function ReadTagged(array $tags, $mode = self::ALL)
	{
		if(!($keys = self::_GetContentKeys($tags, $mode))) return false;

		$return = array();

		foreach($keys as $key)
		{
			if($r = self::Read($key)) $return[] = $r['content'];
		}

		if(empty($return)) return false;

		return $return;
	}

	public static function DeleteTagged(array $tags, $mode = self::ALL)
	{
		if(!($keys = self::_GetContentKeys($tags, $mode, true))) return false;

		$return = false;

		foreach($keys as $key)
		{
			if($r = self::Read($key))
			{
				self::_DisconnectTags($r['tags'], $key);
				self::Delete($key);

				$return = true;
			}
		}

		return $return;
	}

	private static function _DisconnectTags(array $tags, $content_key)
	{
		foreach($tags as $tag)
		{
			$tag_key = 'Tagged:Tags:'.$tag;

			if(!($data = self::Read($tag_key))) continue;

			if(($pos = array_search($content_key, $data)) !== false)
			{
				unset($data[$pos]);

				if(empty($data))
				{
					self::Delete($tag_key);
				}
				else
				{
					self::Write($tag_key, $data, 0);
				}
			}
		}
	}

	private static function _GetContentKeys(array $tags, $mode = self::ALL, $delete = false)
	{
		$content_keys = array();
		$tags_count = count($tags);

		if($tags_count == 0) return false;

		foreach($tags as $tag)
		{
			$tag_key = 'Tagged:Tags:'.$tag;

			if(!($data = self::Read($tag_key))) continue;

			foreach($data as $k => $content_key)
			{
				if(!isset($content_keys[$content_key])) $content_keys[$content_key] = array();

				$content_keys[$content_key][] = $tag;

				if($delete) unset($data[$k]);
			}

			if($delete)
			{
				if(empty($data))
				{
					self::Delete($tag_key);
				}
				else
				{
					self::Write($tag_key, $data);
				}
			}
		}


		if(empty($content_keys)) return false;

		$return = array();

		switch($mode)
		{
			case self::ALL:

				foreach($content_keys as $content_key => $content_tags)
				{
					if(count(array_intersect($content_tags, $tags)) == $tags_count)
					{
						$return[] = $content_key;
					}
				}

			break;
			case self::ANY:

				foreach($content_keys as $content_key => $content_tags)
				{
					if(count(array_intersect($content_tags, $tags)) != 0)
					{
						$return[] = $content_key;
					}
				}

			break;
			case self::NONE:

				foreach($content_keys as $content_key => $content_tags)
				{
					if(count(array_intersect($content_tags, $tags)) == 0)
					{
						$return[] = $content_key;
					}
				}

			break;
		}

		if(empty($return)) return false;

		return $return;
	}

	public static function Read($key)
	{
		if(self::$_options[self::OPT_RUNTIME_MEMORY_CACHE] && isset(self::$_runtime_cache[$key]))
		{
			return self::$_runtime_cache[$key];
		}
		
		self::__initialize();

		foreach(self::$_controllers as $controller)
		{
			if($data = $controller->Read($key))
			{
				return $data;
			}
		}

		return false;
	}
	
	public static function Evaluate($key)
	{
		if($data = self::Read($key))
		{
			$data = str_replace(array('<?php', '<?', '?>'), '', $data);
			return eval($data);
		}
		
		return false;
	}
	
	public static function EvaluateOnce($key)
	{
		static $evaled = array();
		
		if(!isset($evaled[$key]))
		{
			$evaled[$key] = true;
			return self::Evaluate($key);
		}
	}

	public static function Write($key, $var, $expires = 0) //returns true ONLY IF at least one controller returned true
	{
	    $abort = ignore_user_abort(true);
	    
	    if(self::$_options[self::OPT_RUNTIME_MEMORY_CACHE])
	    {
	    	self::$_runtime_cache[$key] = $var;
	    }
	    
		self::__initialize();

		$return = false;

		foreach(self::$_controllers as $controller)
		{
			if($controller->Write($key, $var, $expires)) $return = true;
		}
		
		ignore_user_abort($abort);

		return $return;
	}

	public static function Delete($key) //returns true ONLY IF all controllers returned true
	{
	    $abort = ignore_user_abort(true);
	    
	    if(isset(self::$_runtime_cache[$key]))
	    {
	    	unset(self::$_runtime_cache[$key]);
	    }
	    
		self::__initialize();

		$return = true;

		foreach(self::$_controllers as $controller)
		{
			if(!$controller->Delete($key)) $return = false;
		}
		
		ignore_user_abort($abort);

		return $return;
	}

	public static function Clean() //returns true ONLY IF all controllers returned true
	{
	    $abort = ignore_user_abort(true);
	    
		self::__initialize();
		
		//self::$_runtime_cache = array();

		$return = true;

		foreach(self::$_controllers as $controller)
		{
			if(!$controller->Clean()) $return = false;
		}
		
		if(!self::_cleanCacheDir()) $return = false;
		
		ignore_user_abort($abort);

		return $return;
	}
	
	private static function _cleanCacheDir($dir = null)
	{
		if($dir == null) $dir = CACHE_DIR.DS.'Sites'.DS.CURRENT_SITE;
		
		try
		{
			$d = opendir($dir);
			
			while($f = readdir($d))
			{
				if($f == '.' || $f == '..') continue;
				
				if(is_dir($dir.DS.$f))
				{
					if(!self::_cleanCacheDir($dir.DS.$f))
					{
						closedir($d);
						return false;
					}
					
					if(!@rmdir($dir.DS.$f))
					{
						return false;
					}
				}
				else
				{
					unlink($dir.DS.$f);
				}
			}
			
			closedir($d);
			
			return true;
		}
		catch(Exception $e)
		{
			return false;
		}
	}
}