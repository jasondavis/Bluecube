<?php
Engine::Using('System.Object');
Engine::Using('System.Interfaces.Singleton');

/**
 * TApplication class
 *
 *
 *
 */

class TApplication extends TObject implements ISingleton
{
	/**
	 * Returns application's instance
	 *
	 * @return TApplication
	 */

	protected static $instance;
	protected static $applicationStart_timestamp;

	public static function getInstance()
	{
		if(!self::$instance) self::$instance = new TApplication;
		return self::$instance;
	}

	public function __construct()
	{
		if(self::$instance) throw new CoreException('Only one instance of TApplication is allowed');
	}

	public function Execute()
	{
		if(is_array($_SERVER) && isset($_SERVER['HTTP_HOST']))
		{
			$this->RunWebEnvironment(); //Application is requested via web server
		}
		else
		{
			$this->RunShellEnvironment(); //Application is executed via shell
		}
	}

	/**
	 * Executes as web application
	 *
	 * @return unknown_type
	 */

	private function RunWebEnvironment()
	{
		self::$applicationStart_timestamp = microtime(true);
		
		Engine::Using('System.Http.HttpRouting');
		Engine::Using('System.Http.HttpRequest');
		Engine::Using('System.Http.HttpResponse');
		Engine::Using('System.Http.HttpCache');
		Engine::Using('System.Security.AuthManager');
		
		if(!($cfg = Engine::GetConfig('site')))
		{
			throw new CoreException('No sites configuration found');
		}

		$req_host = $_SERVER['HTTP_HOST'];
		$req_host2 = '.'.$_SERVER['HTTP_HOST'];

		foreach($cfg as $host)
		{
			if(substr($host['host'], 0, 1) == '.') //.host_name
			{
				$org_host = $host['host'];
				$host['host'] = str_replace('.', '\.', $host['host']);

				if($org_host == $req_host2 || preg_match("{.+{$host['host']}}", $req_host))
				{
					define('CURRENT_SITE', $host['name']);
					define('SITE_ROOT', SITES_DIR.DS.CURRENT_SITE);
					break;
				}
			}
			else if($host['host'] == $req_host) //host_name
			{
				define('CURRENT_SITE', $host['name']);
				define('SITE_ROOT', SITES_DIR.DS.CURRENT_SITE);
				break;
			}
		}
		
		if(isset($host['engine.mode']))
        {
            switch($host['engine.mode'])
            {
                case 'performance': Engine::setMode(Engine::MODE_PERFORMANCE); break;
                case 'debug': Engine::setMode(Engine::MODE_DEBUG); break;
                default: throw new CoreException('Unknown engine.mode value: '.$host['engine.mode']); 
            }
        }

		if(!defined('CURRENT_SITE'))
		{
			throw new CoreException('There is no site in webconfig.xml that matches current HTTP host');
		}

		if(!Engine::is_dir(SITE_ROOT))
		{
			throw new CoreException('Site '.CURRENT_SITE.' could not be found under '.SITE_ROOT);
		}

		chdir(SITE_ROOT); //change current working directory to SITE_ROOT
		
		TAuthManager::StartSession();
		
		THttpRouting::Route();
		
		THttpCache::Initialize();

		try
		{
			if(!class_exists($_GET['page']))
			{
				throw new CoreException();
			}
		}
		catch(CoreException $e)
		{
			if(Engine::getMode() == Engine::MODE_PERFORMANCE)
			{
				throw new ResponseError(404);
			}
			else
			{
				throw new CoreException("Page or service '{$_GET['page']}' could not be found");
			}
		}

		if($_GET['page'] != 'TMediaService')
		{
			TAuthManager::CheckAuth($_GET['page'], $_GET['action']);
		}
		
		if(is_subclass_of($_GET['page'], 'TPage'))
		{
			$gzip = false;
			
			if(strpos(THttpRequest::AcceptEncoding(), 'x-gzip') !== false)
			{
				$gzip = 'x-gzip';
			}
			else if(strpos(THttpRequest::AcceptEncoding(), 'gzip') !== false)
			{
				$gzip = 'gzip';
			}
			else if(strpos(THttpRequest::AcceptEncoding(), 'deflate') !== false)
			{
				$gzip = 'deflate';
			}
		
			if(false && $gzip)
			{
				THttpResponse::setHeader('Content-Encoding', $gzip);
				ob_start();
			}
			
			$this->RunRequestedPage();
			
			if(false && $gzip)
			{
				$contents = ob_get_clean();
        		$size = strlen($contents);
        		$contents = gzencode($contents, 9, $gzip == 'deflate' ? FORCE_DEFLATE : FORCE_GZIP);
        		$contents = substr($contents, 0, $size);
				THttpResponse::setHeader('Content-Length', strlen($contents));
				echo $contents;
			}
		}
		else if(is_subclass_of($_GET['page'], 'TService'))
		{
			$this->RunRequestedService();
		}
		else
		{
			throw new Exception("'{$_GET['page']}' must inherit from TPage or TService");
		}
	}

	/**
	 * Runs requested page
	 *
	 * @return unknown_type
	 */

	private function RunRequestedPage()
	{
		$start = microtime(true);
		
		Engine::Using('System.Web.Controls.Page');

		$class = $_GET['page'];

		/*
		 * check whether there is a cached version of requested page for the same request
		 */

		$cache_identifier = 'Pages:'.$class.':'.THttpRequest::getHash();
		
		if(Engine::getMode() == Engine::MODE_PERFORMANCE && THttpRequest::RequestMethod() == 'GET' && ($cached = TCache::Read($cache_identifier)))
		{
			$this->__setInfoHeaders($start);
			THttpResponse::setHeader('X-Cache-Content', 'true');
			THttpResponse::setHeader('X-Cache-Expires', gmdate('D, d M Y H:i:s', $cached['expires']).' GMT');
			
			THttpCache::CheckEtag(md5($cached['content']));
	
			echo $cached['content'];

			return;
		}

		/*
		 * no cache, process request normally
		 */

		$stack = array(); //array of pages hierachy: page , parent page , parent parent page and so on

		do
		{
			$stack[] = new $class;
		}
		while(($class = get_parent_class($class)) != 'TPage');

		$stack_length = count($stack);

		ob_start();

		/*
		 * starting from the most low-level page, call each
		 * page to enter the next step of life cycle
		 */
		for($i = $stack_length-1; $i >= 0; $i--)
		{
			if($stack[$i]->getHasNextStartStep())
			{
				$stack[$i]->EnterNextStartStep();
				if($i%$stack_length == 0) $i = $stack_length;
			}
		}

		/*
		 * starting from the most top-level page, call each
		 * page to render its content
		 */
		for($i = 0; $i < $stack_length; $i++)
		{
			if($stack[$i]->getHasNextEndStep())
			{
				$stack[$i]->EnterNextEndStep();
				if($i%$stack_length == 0) $i = 0;
			}
		}

		/*
		 * if requested page has set cache expire time, get the output buffer,
		 * cache the content, and finally echo the content
		 */

		$expires = $stack[0]->getCacheExpires();
		
		if($expires == -1)
		{
		    $server_cache = THttpRouting::getServerCacheConfig();
		    if(!empty($server_cache))
		    {
		    	$expires = $server_cache['expires'];
		    }
		}
		
		$data = array(
			//'headers' => headers_list(),
			'content' => trim(ob_get_clean()),
		    'created' => time(),
			'expires' => time() + $expires 
		);
		
		if(Engine::getMode() == Engine::MODE_PERFORMANCE && THttpRequest::RequestMethod() == 'GET' && $expires > -1)
		{
			TCache::Write($cache_identifier, $data, $expires);
		}
		
		$this->__setInfoHeaders($start);
		THttpResponse::setHeader('X-Cache-Content', 'false');
		
		THttpCache::CheckEtag(md5($data['content']));
		
		echo $data['content'];
	}

	/**
	 * Runs requested service
	 *
	 * @return unknown_type
	 */

	private function RunRequestedService()
	{
		$start = microtime(true);
				
		Engine::Using('System.Web.Service');
		
		$class = $_GET['page'];
		
		$cache_identifier = 'Services:'.$class.':'.THttpRequest::getHash();
		
		if(Engine::getMode() == Engine::MODE_PERFORMANCE && !($class instanceOf TMediaService) && THttpRequest::RequestMethod() == 'GET' && ($cached = TCache::Read($cache_identifier)))
		{
			$this->__setInfoHeaders($start);
			THttpResponse::setHeader('X-Cache-Content', 'true');
			THttpResponse::setHeader('X-Cache-Expires', gmdate('D, d M Y H:i:s', $cached['expires']).' GMT');
			
			THttpCache::CheckEtag(md5($cached['content']));

			echo $cached['content'];
			
			return;
		}
		
		ob_start();
		$service = new $class;
		
		$expires = $service->getCacheExpires();
		
		if($expires == -1)
		{
		    $server_cache = THttpRouting::getServerCacheConfig();
		    if(!empty($server_cache))
		    {
		    	$expires = $server_cache['expires'];
		    }
		}
		
		$data = array(
			//'headers' => headers_list(),
			'content' => trim(ob_get_clean()),
		    'created' => time(),
			'expires' => time() + $expires 
		);

		if(Engine::getMode() == Engine::MODE_PERFORMANCE && !($class instanceOf TMediaService) && THttpRequest::RequestMethod() == 'GET' && $expires > -1)
		{
			TCache::Write($cache_identifier, $data, $expires);
		}
		
		$this->__setInfoHeaders($start);
		THttpResponse::setHeader('X-Cache-Content', 'false');
		
		THttpCache::CheckEtag(md5($data['content']));
		
		echo $data['content'];
	}
	
	private function __setInfoHeaders($start)
	{
		$now = microtime(true);
		$page_exec_duration = $now-$start;
		THttpResponse::setHeader('X-Powered-By', Engine::IDENTIFIER.' '.Engine::VERSION);
		THttpResponse::setHeader('X-Engine-Time', $now-self::$applicationStart_timestamp-$page_exec_duration);
		THttpResponse::setHeader('X-Service-Time', $page_exec_duration);
		THttpResponse::setHeader('X-Total-Time', $now-self::$applicationStart_timestamp);
		THttpResponse::setHeader('X-Memory-Usage', (memory_get_usage(false)/1024).' KB');
		THttpResponse::setHeader('X-Engine-Mode', Engine::getMode() == Engine::MODE_DEBUG ? 'debug' : 'performance');
	}

	/**
	 * Executes as shell application
	 *
	 * @return unknown_type
	 */

	private function RunShellEnvironment()
	{
		global $argv, $argc;
		
		$path = dirname(dirname(realpath($argv[0])));
		
		define('SITE_ROOT', $path);
		define('CURRENT_SITE', substr($path, strrpos($path, DS)+1));
		
		try
		{
			Engine::Using('System.Autoloader');
			Engine::Using('Debug.ErrorHandler');
		
			$class = substr(basename($argv[0]), 0, strpos(basename($argv[0]), '.'));
		
			$app = new $class;
		
			$app->main($argc, $argv);
		}
		catch(Exception $e)
		{
			echo $e->__toString();
		}
	}
}
