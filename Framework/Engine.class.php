<?php
/**
 *
 */
error_reporting(E_ALL | E_STRICT);

function stripslashes_recursive(&$data)
{
	if(is_array($data))
	{
		foreach($data as $k => $v)
		{
			$data[$k] = stripslashes_recursive($v);
		}
	}
	else
	{
		$data = stripslashes(stripslashes($data));
	}

	return $data;
}

stripslashes_recursive($_GET);
stripslashes_recursive($_POST);
stripslashes_recursive($_COOKIE);

ini_set('magic_quotes_gpc', 'off');
ini_set('register_globals', 'off');
ini_set('display_errors', 'on');
ini_set('html_errors', 'off');
ini_set('docref_root', '');
ini_set('date.timezone', 'Europe/Warsaw');

define('DS', DIRECTORY_SEPARATOR); //just for a shortcut

define('ROOT_DIR', dirname(dirname(__FILE__)));
define('SITES_DIR', ROOT_DIR.DS.'Sites');
define('CACHE_DIR', ROOT_DIR.DS.'Cache');
define('FRAMEWORK_DIR', dirname(__FILE__));
define('SYSTEM_DIR', FRAMEWORK_DIR.DS.'System');

if(!empty($_ENV['TMP']))	define('SYS_TMP_DIR', realpath($_ENV['TMP']));
else if(!empty($_ENV['TMPDIR']))	define('SYS_TMP_DIR', realpath($_ENV['TMPDIR']));
else if(!empty($_ENV['TEMP']))	define('SYS_TMP_DIR', realpath($_ENV['TEMP']));
else define('SYS_TMP_DIR', '/tmp');

/**
 * CoreException class
 *
 * This exception is thrown by the engine
 *
 *
 */

class CoreException extends Exception {}

/**
 * Engine class. The core of Framework
 *
 *
 *
 */

class Engine
{
	const MODE_DEBUG = 0;
	const MODE_PERFORMANCE = 1;

	const IDENTIFIER = 'Bluecube Framework';
	const VERSION = '1.0';

	const WEBCONFIG = 0;
	const SITECONFIG = 1;

	private static $mode = 0;
	private static $loaded_namespaces = array();

	/**
 	* Using() method is used for loading namespaces. Supports the following
 	* namespace formats:
 	*
 	* load.some.library - loads 'library' from 'load.some' namespace
 	* load.some.libraries.* - loads all libraries from 'load.some.libraries' namespace
 	*
 	* By default, looks for namespace in Framework's directory. You can change this
 	* behavior using special namespaces.
 	*
 	* Special namespaces:
 	*
 	* @Root.namespace.path - points to /Runtime directory
 	* @Site.namespace.path - points to current site /Runtime directory
 	*
 	* @param $namespace
 	* @return void
 	*/


	public static function Using($namespace)
	{
		if(isset(self::$loaded_namespaces[$namespace])) return;

		$ex = explode('.', $namespace);
		$last = array_pop($ex);
		$first = $ex[0];

		if(substr($first,0, 1) == '@')
		{
			unset($ex[0]);

			switch($first)
			{
				case '@Root':
					$path = ROOT_DIR.DS.'Runtime'.DS.implode(DS, $ex);
				break;
				case '@Site':
					$path = SITE_ROOT.DS.'Runtime'.DS.implode(DS, $ex);
				break;
			}
		}
		else
		{
			$path = FRAMEWORK_DIR.DS.implode(DS, $ex);
		}

		if($last == '*')
		{
			if(is_dir($path))
			{
				$dir = opendir($path);

				while($f = readdir($dir))
				{
					if(is_file($path.DS.$f) && preg_match('{^T[a-zA-Z0-9\.]+\.class\.php$}', $f))
					{
						include_once $path.DS.$f;
					}
				}
				closedir($dir);
			}
			else
			{
				die('<b>Fatal error:</b> Namespace '.$namespace.' does not exist');
			}
		}
		else
		{
			if(strpos($path, 'System'.DS.'Interfaces'))
			{
				$prefix = 'I';
			}
			else
			{
				$prefix = 'T';
			}
			$last = $prefix.$last.'.class.php';
			$path .= DS.$last;

			if(is_file($path))
			{
				include_once $path;
			}
			else
			{
				die('<b>Fatal error:</b> Namespace '.$namespace.' does not exist');
			}
		}

		self::$loaded_namespaces[$namespace] = true;
	}

	/**
	 * Sets engine's mode. Currently supported modes:
	 *
	 * Engine::MODE_DEBUG - mainly used for debugging purposes
	 * Engine::PERFORMANCE - should only be used in production environment
	 *
	 * @param $mode
	 * @return void
	 */

	public static function setMode($mode)
	{
		if($mode == self::$mode) return;

		$ref = new ReflectionClass('Engine');

		$modes = $ref->getConstants();
		$mode = (int) $mode;

		if(!in_array($mode, $modes)) throw new CoreException("Engine mode invalid");

		self::$mode = $mode;
	}

	/**
	 * Returns current engine mode
	 *
	 * @return int
	 */

	public static function getMode()
	{
		return self::$mode;
	}

	/**
	 * Writes the content into cache under given identifier.
	 *
	 * @param $content
	 * @param $identifier
	 * @param $expires
	 * @return unknown_type
	 */

	/*
	 * @TODO Allow user to change the cache controller using configuration files
	 */

	public static function WriteCache($content, $identifier, $expires = 0)
	{
		if(defined('CURRENT_SITE'))
		{
			$sub = 'Sites'.DS.CURRENT_SITE.DS;
		}
		else
		{
			$sub = '';
		}
		
		if(TCache::getEnabled())
		{
			TCache::Write($identifier, $content, $expires);
		}
		else
		{
			$identifier = str_replace(':',DS,$identifier).'.cache';
			$dirname = CACHE_DIR.DS.$sub.dirname($identifier);

			if(!is_dir($dirname)) @mkdir($dirname, 0775, 1);
			file_put_contents(CACHE_DIR.DS.$sub.$identifier, $content, LOCK_EX);
			@chmod(CACHE_DIR.DS.$sub.$identifier, 0775);

			if($expires > 0)
			{
				file_put_contents(CACHE_DIR.DS.$sub.$identifier.'.expires', time()+$expires, LOCK_EX);
			}
		}
	}

	/**
	 * Includes cached content as PHP file
	 *
	 * @param $identifier
	 * @return unknown_type
	 */

	public static function IncludeCache($identifier)
	{
		if(!self::IsCached($identifier)) return false;

		if(defined('CURRENT_SITE'))
		{
			$sub = 'Sites'.DS.CURRENT_SITE.DS;
		}
		else
		{
			$sub = '';
		}

		if(TCache::getEnabled())
		{
			TCache::EvaluateOnce($identifier);
		}
		else
		{
			$file = CACHE_DIR.DS.$sub.str_replace(':',DS,$identifier).'.cache';

			include_once $file;
		}
	}

	public static function GetCachePath($identifier)
	{
		if(!self::IsCached($identifier)) return false;

		if(defined('CURRENT_SITE'))
		{
			$sub = 'Sites'.DS.CURRENT_SITE.DS;
		}
		else
		{
			$sub = '';
		}

		return CACHE_DIR.DS.$sub.str_replace(':',DS,$identifier).'.cache';
	}

	/**
	 * Checks whether cache under given identifier exists ans is not expired
	 *
	 * @param $identifier
	 * @return unknown_type
	 */

	public static function IsCached($identifier)
	{
		if(defined('CURRENT_SITE'))
		{
			$sub = 'Sites'.DS.CURRENT_SITE.DS;
		}
		else
		{
			$sub = '';
		}
		
		if(TCache::getEnabled())
		{
			return TCache::Read($identifier) ? true : false;
		}
		else
		{
			$cache_file = CACHE_DIR.DS.$sub.str_replace(':',DS,$identifier).'.cache';
			$expire_file = $cache_file.'.expires';

			if(file_exists($expire_file))
			{
				$expires = (int) @file_get_contents($expire_file, LOCK_EX);
				if(time() > $expires)
				{
					@unlink($cache_file);
					return false;
				}
			}

			return file_exists($cache_file);
		}
	}

	/**
	 * Returns cached content or false on failure
	 *
	 * @param $identifier
	 * @return unknown_type
	 */

	public static function ReadCache($identifier)
	{
		if(!self::IsCached($identifier))
		{
			return false;
		}

		if(defined('CURRENT_SITE'))
		{
			$sub = 'Sites'.DS.CURRENT_SITE.DS;
		}
		else
		{
			$sub = '';
		}
		
		if(TCache::getEnabled())
		{
			return TCache::Read($identifier);
		}
		else
		{
			$cache_file = CACHE_DIR.DS.$sub.str_replace(':',DS,$identifier).'.cache';

			try
			{
				return file_get_contents($cache_file, LOCK_EX);
			}
			catch(Exception $e)
			{
				return false;
			}
		}
	}

	/**
	 * Gets the config values from webconfig.xml under
	 * specified xpath. Returns false on failure.
	 *
	 * @param $xpath
	 * @return unknown_type
	 */

	public static function GetConfig($xpath, $cfgFile = self::WEBCONFIG, $useHostConfig = true)
	{
		if(!isset($_SERVER['HTTP_HOST'])) $useHostConfig = false;
		
		$xpath = ltrim($xpath,'/');
		$queryPath = $xpath;
		//if(!$dom)
		//{
			if($cfgFile == self::WEBCONFIG)
			{
				$path = ROOT_DIR.DS.'webconfig.xml';
				$xpath = '//webconfig/'.$xpath;
			}
			else if($cfgFile == self::SITECONFIG)
			{
				if($useHostConfig)
				{
					$path = ROOT_DIR.DS.'webconfig.xml';
					$xpath = '//webconfig/site[@host="'.$_SERVER['HTTP_HOST'].'"]/'.$xpath;
				}
				else
				{
					$path = SITE_ROOT.DS.'siteconfig.xml';
					$xpath = '//siteconfig/'.$xpath;
				}
			}
			else
			{
				throw new CoreException('Unknown config to read');
			}

			if($cfgFile == self::SITECONFIG && self::GetMode() == Engine::MODE_PERFORMANCE)
			{
				$cache_key = 'Config:'.md5($path.$xpath);

				if($cached = self::ReadCache($cache_key))
				{
					return TObject::Unserialize($cached);
				}
			}

			if(!self::file_exists($path)) throw new CoreException($path.': file not found');

			static $doms = array();
			static $xps = array();

			if(!isset($doms[$path]) || !isset($xps[$path]))
			{
				$doms[$path] = $_dom = new DOMDocument;
				$doms[$path]->load($path);
				
				do
				{
					$includes = $_dom->getElementsByTagName('include');
					
					for($i = 0; $i < $includes->length; $i++)
					{
						$include = $includes->item($i);

						$fragment = $_dom->createDocumentFragment();
						$fragment->appendXML(file_get_contents(dirname($path).DS.$include->getAttribute('file')));
						$include->parentNode->replaceChild($fragment, $include);
					}
				}
				while($includes->length > 0);
				
				$xps[$path] = new DOMXPath($doms[$path]);
			}

			$dom = $doms[$path];
			$xp = $xps[$path];
		//}

		$ret = $xp->Query($xpath);

		if(count($ret) > 0)
		{
			$return = array();

			foreach($ret as $node)
			{
				$attrs = array();
				foreach($node->attributes as $attr => $value)
				{
					$attrs[$attr] = TVar::autoCast($value->value);
				}
				$return[] = $attrs;
			}
		}
		else
		{
			$return = false;
		}

		if($return == false && $cfgFile == self::SITECONFIG && $useHostConfig) //no value in siteconfig, ask webconfig
		{
			$return = self::GetConfig($queryPath, self::SITECONFIG, false);
		}

		if($return == false && $cfgFile == self::SITECONFIG && !$useHostConfig)
		{
			$return = self::GetConfig($queryPath, self::WEBCONFIG);
		}

		if($cfgFile == self::SITECONFIG && self::GetMode() == Engine::MODE_PERFORMANCE)
		{
			$cache_key = 'Config:'.md5($path.$xpath);
			self::WriteCache(TObject::Serialize($return), $cache_key, 0);
		}

		return $return;
	}

	/**
	 * Tuned file_exists function. This function will
	 * always return true if MODE_PERFORMANCE is set.
	 * Otherwise will execute original file_exists()
	 *
	 * @param $file
	 * @return boolean
	 */

	public static function file_exists($file)
	{
		if(self::$mode == self::MODE_PERFORMANCE) return true;

		return file_exists($file);
	}

	/**
	 * Tuned is_dir function. This function will
	 * alweays return true if MODE_PERFORMANCE is set.
	 * Otherwise will execute original is_dir()
	 *
	 * @param $path
	 * @return unknown_type
	 */

	public static function is_dir($path)
	{
		if(self::$mode == self::MODE_PERFORMANCE) return true;

		return is_dir($path);
	}
}

Engine::Using('System.Object'); //load the root - TObject class
Engine::Using('Debug.ErrorHandler');
Engine::Using('Debug.ExceptionHandler');
Engine::Using('Debug.ExceptionDisplay');
Engine::Using('System.Caching.Cache');