<?php
Engine::Using('System.Object');

/**
 * THttpUrl class
 * 
 * This class is responsible for creating nice urls
 * from given array of parameters according to routing
 * rules defined in siteconfig.xml file
 * 
 * 
 */

class THttpUrl extends TObject
{
	private $_params = array();
	
	public function __construct(array $params = array())
	{
		foreach($params as $k => $param)
		{
			if($param === '' || $param === null) continue;
			
			$this->_params[$k] = (string) $param;
		}
		
	}
	
	public function __toString()
	{
		try
		{
			static $default_format = false;
			static $encode_function = false;
			
			if(!$default_format)
			{
				$default_format = Engine::GetConfig('routing/option[@name="default_format"]', Engine::SITECONFIG);
			    $default_format = (isset($default_format[0]['value'])) ? $default_format[0]['value'] : '[^/]+';
			}
			
			if(!$encode_function)
			{
                $encode_function = Engine::GetConfig('routing/option[@name="encode_function"]', Engine::SITECONFIG);
                $encode_function = (isset($encode_function[0]['value'])) ? $encode_function[0]['value'] : 'urlencode';
			}
			
			$params = &$this->_params;
			
			ksort($params);
			
			$rendered_url = false;
		
			if(Engine::GetMode() == Engine::MODE_PERFORMANCE)
			{
				$p = isset($params['page']) ? $params['page'] : '';
				$a = isset($params['action']) ? $params['action'] : '';
				
				$cache_identifier = 'Routing:Route:'.md5($_SERVER['HTTP_HOST'].':'.var_export(array_keys($params), 1).':'.$p.':'.$a);
				//$rendered_url = Engine::ReadCache($cache_identifier);
				$rendered_url = TCache::Read($cache_identifier);
			}
			
			if(!$rendered_url)
			{
				$host = $_SERVER['HTTP_HOST'];
				
				$routes = array_merge(
                    Engine::GetConfig('routing/route[@host="'.$host.'"]', Engine::SITECONFIG),
                    Engine::GetConfig('routing/route[not(@host)]', Engine::SITECONFIG)
                );
	
				$params_count = count($params);
			
				foreach($routes as $k => $route)
				{
					$variables = array(); //defined as {varname}
					$constants = array(); //defined as set.varname="value"
									
					$is_matching = true;
			
					if(preg_match_all('@{(?P<name>[a-zA-Z0-9_]+)}@', $route['url'], $matches))
					{
						$variables = $matches['name'];
					}
				
					foreach($route as $varname => $varvalue)
					{
						if(($pos = strpos($varname, '.')) && substr($varname, 0, $pos) == 'set')
						{
							$const = substr($varname, $pos+1);
							$constants[$const] = $varvalue;
						
							if(!isset($this->_params[$const]) || $this->_params[$const] != $varvalue)
							{
								$is_matching = false;
								break;
							}
						}
					}
				
					if(!$is_matching)
					{
						continue;
					}
			
					if(count($variables) + count($constants) == $params_count) //potentially matching rule
					{
						$variables_names = array_merge(array_keys($constants), $variables);
						$params_names = array_keys($this->_params);
					
						$difference = array_diff($variables_names, $params_names);
					
						if(!empty($difference)) //$difference should be 0
						{
							continue;
						}
					
						//if we're here, everything looks almost good. let's check values against values regular expressions
				
						foreach($this->_params as $name => $param)
						{
							$regexp = isset($route[$name]) ? $route[$name] : $default_format;
					
							if(!preg_match('{'.$regexp.'}', $param))
							{
								$is_matching = false;
								break;
							}
						}
					
						if(!$is_matching) 
						{
							continue;	
						}
					
					//hurray! if we're here, everything is OK! let's render the url!
				
						$rendered_url = $route['url'];
					
						if(Engine::GetMode() == Engine::MODE_PERFORMANCE)
						{
							//Engine::WriteCache($rendered_url, $cache_identifier, 0);
							TCache::Write($cache_identifier, $rendered_url, 0);
						}
				
						break;
					}
				} //end foreach($rules)
			}
		}
		catch(Exception $e)
		{
			$rendered_url = false;
		}
		
		if($rendered_url)
		{
			foreach($this->_params as $variable => $value)
			{
				$rendered_url = str_replace('{'.$variable.'}', $encode_function($value), $rendered_url);
			}
		}
		else //seems no rule match, render ugly url
		{
			$rendered_url = '/?';
			foreach($this->_params as $param => $value)
			{
				$rendered_url .= ($encode_function($param)).'='.($encode_function($value)).'&';
			}
			$rendered_url = substr($rendered_url, 0, -1);
		}
		
		return $rendered_url;
	}
}