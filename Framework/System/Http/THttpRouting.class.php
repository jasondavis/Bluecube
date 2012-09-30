<?php
Engine::Using('System.Object');
Engine::Using('System.Http.HttpUrl');

class THttpRouting extends TObject
{
	protected static $server_cache = array();
	protected static $client_cache = array();
	
	/**
	 * Routes REQUEST URI to GET variables
	 * 
	 * @return unknown_type
	 */
	public static function Route()
	{
		static $routed;
		
		if($routed) return;
		
		$routed = true;
		
		$host = $_SERVER['HTTP_HOST'];
		
		$routes = array_merge(
		      Engine::GetConfig('routing/route[@host="'.$host.'"]', Engine::SITECONFIG),
		      Engine::GetConfig('routing/route[not(@host)]', Engine::SITECONFIG)
		);
		
		$routes[] = array(
			'url' => '/Images/{path}',
            'path' => '.+',
            'set.page' => 'TMediaService',
            'set.action' => 'GetImage'
        );
        
        $routes[] = array(
			'url' => '/Styles/{path}',
            'path' => '.+',
            'set.page' => 'TMediaService',
            'set.action' => 'GetStyle'
        );
        
        $routes[] = array(
			'url' => '/Scripts/{path}',
            'path' => '.+',
            'set.page' => 'TMediaService',
            'set.action' => 'GetScript'
        );
        
        $routes[] = array(
			'url' => '/Assets/{path}',
            'path' => '.+',
            'set.page' => 'TMediaService',
            'set.action' => 'GetAsset'
        );
		
		$opts = Engine::GetConfig('routing/option', Engine::SITECONFIG);
		
		foreach($opts as $opt)
		{
			$options[$opt['name']] = $opt['value'];
		}
		
		$uri = $options['decode_function']($_SERVER['REQUEST_URI']);
		
		if($pos = strpos($uri,'?'))
		{
			$uri = substr($uri,0,$pos);
		}
		
		if(Engine::GetMode() == Engine::MODE_PERFORMANCE) //check cache for cached route
		{
			$cache_key = 'Routing:'.md5($_SERVER['HTTP_HOST'].$uri);
		
			if($cached = TCache::Read($cache_key))
			{
				$route = TObject::Unserialize($cached);
			
				self::$client_cache = $route['client_cache'];
				self::$server_cache = $route['server_cache'];
				
				self::MergeGetVars($route['vars']);
				
				return;
			}
		}
		
		foreach($routes as $route)
		{
			if(!isset($route['url'])) throw new CoreException('Invalid route: url attribute not found');
			
			if(preg_match_all('@{(?P<var>[a-zA-Z0-9_]+)}@', $route['url'], $matches)) //match all variable names from rule
			{
				$vars = array();
				foreach($matches['var'] as $var)
				{
					$format = isset($route[$var]) ? $route[$var] : $options['default_format']; //apply format pattern
					$vars[$var] = $format;
				}
				
				$rule = self::CreateRule($route['url'], $vars); //create regex rule from config rule and list of vars
				$vars = self::MatchUri($uri, $rule, array_keys($vars)); //match uri against rule and list of vars names
				
				if($vars) //match successful
				{	
					self::doRoute($host, $route, $vars, $options);
					
					return;
				}
			}
			else if($uri == $route['url'])
			{	
				self::doRoute($host, $route, array(), $options);
				
				return;
			}
		}
		
		/*
		//if no rules match, set defaults
		if(!isset($_GET['page'])) $_GET['page'] = $options['default_page'];
		if(!isset($_GET['action'])) $_GET['action'] = $options['default_action'];
		*/
		/*if(!isset($_GET['page'])) $_GET['page'] = $options['default_page'];
		if(!isset($_GET['action'])) $_GET['action'] = $options['default_action'];*/
		
		if($options['default_page'] == null)
		{
			throw new ResponseError(404);
		}
		
		$_GET['page'] = $options['default_page'];
		$_GET['action'] = $options['default_action'];
		
		//unset($uri, $routes, $route, $vars, $options, $matches, $rule, $var, $cache_key, $uri, $opts); //cleanup
	}
	
	protected static function doRoute($host, $route, $vars, $options)
	{
        self::AddSets($route, $vars, $options);
        self::MergeGetVars($vars);
        
	    $cache = Engine::GetConfig('routing/route[@host="'.$host.'" and @url="'.$route['url'].'"]/cache', Engine::SITECONFIG);
        
        if(empty($cache))
        {
            $cache = Engine::GetConfig('routing/route[not(@host) and @url="'.$route['url'].'"]/cache', Engine::SITECONFIG);
        }
                    
        if(!empty($cache))
        {
            foreach($cache as $config)
            {
            	switch($config['type'])
            	{
            		case 'server': self::$server_cache = $config; break;
            		case 'client': self::$client_cache = $config; break;
            	}
            }
        }
        
	    if(Engine::GetMode() == Engine::MODE_PERFORMANCE)
        {
            $uri = $_SERVER['REQUEST_URI'];
        
            if($pos = strpos($uri,'?'))
            {
                $uri = substr($uri,0,$pos);
            }
            
            $cache_key = 'Routing:'.md5($_SERVER['HTTP_HOST'].$uri);
            
            TCache::Write($cache_key,
                TObject::Serialize(
                    array(
                        'vars' => $vars,
                        'server_cache' => self::$server_cache,
                        'client_cache' => self::$client_cache
                    )
                ),
            0);
        }
	} 
	
	/**
	 * Merges GET variables with vars
	 * 
	 * @param $vars
	 * @return unknown_type
	 */
	
	private static function MergeGetVars($vars)
	{
		$_GET = array_merge($vars, $_GET);
	}
	
	/**
	 * Adds set.<name> variables to vars set and apply defaults if page and action not set
	 * 
	 * @param $route
	 * @param $vars
	 * @return void
	 */
	
	private static function AddSets($route, &$vars, $options)
	{
		foreach($route as $param_name => $param)
		{
			if(preg_match_all('{^set\.(?<name>.+)$}', $param_name, $matches))
			{
				$vars[$matches['name'][0]] = $param;
			}
		}
		
		if(!isset($vars['page'])) $vars['page'] = $options['default_page'];
		if(!isset($vars['action'])) $vars['action'] = $options['default_action'];
		
		//unset($route, $options); //cleanup
	}
	
	/**
	 * Creates rule from url and set of vars
	 * 
	 * @param $url
	 * @param $vars
	 * @return string
	 */
	
	private static function CreateRule($url, $vars)
	{
		foreach($vars as $var_name => $format)
		{
			$url = str_replace('{'.$var_name.'}', "(?P<$var_name>$format)", $url);
		}
		
		return '{^'.$url.'$}';
	}
	
	/**
	 * Checks whether uri matches given rule and list of vars
	 * 
	 * @param $uri
	 * @param $rule
	 * @param $vars_names
	 * @return array
	 */
	
	private static function MatchUri($uri, $rule, array $vars_names)
	{
		try
		{
			if(!preg_match_all($rule, $uri, $matches)) return false;
		}
		catch(Exception $e)
		{
			throw new CoreException('You have a syntax error in route rule: '.$rule);
		}
		
		$return = array();
		
		foreach($vars_names as $var)
		{
			if(!isset($matches[$var])) return false;
			
			$return[$var] = $matches[$var][0];
		}
		
		return $return;
	}
	
	/**
	 * Renders URL according to given parameters and routing rules - you can use THttpUrl class instead
	 * 
	 * @param $params array
	 * @return string
	 */
	
	public static function RenderUrl(array $params)
	{
		return (string) new THttpUrl($params);
	}
	
	public static function getClientCacheConfig()
	{
		return self::$client_cache;
	}
	
	public static function getServerCacheConfig()
	{
		return self::$server_cache;
	}
}
