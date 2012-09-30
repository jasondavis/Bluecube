<?php
Engine::Using('System.Http.HttpResponse');

class THttpCache extends TObject
{
	private static $_etag;
	private static $_session_sensitive = false;
	
	public static function CheckEtag($etag)
	{
		if(self::$_session_sensitive)
		{
			$etag .= '.'.session_id();
		}
		
		THttpResponse::setHeader('Etag', $etag);
		
		if(isset($_SERVER['HTTP_IF_NONE_MATCH']))
		{
			if(trim($_SERVER['HTTP_IF_NONE_MATCH']) == $etag)
			{
    			THttpResponse::setCode(304);
    			exit;
			}
		}
	}
	
	public static function CheckModified($last_modified_time)
	{
		THttpResponse::setHeader('Last-Modified', gmdate('D, d M Y H:i:s', $last_modified_time).' GMT');
		
		if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $last_modified_time)
		{
			THttpResponse::setCode(304);
			exit;
		} 
	}
	
	public static function Initialize()
	{
		if(isset($_GET['logout']) || THttpRequest::RequestMethod() == 'POST')
		{
			return;
		}
		
		$uri = THttpRequest::RequestUri();
		
		if(($pos = strpos($uri, '?')) !== false)
		{
			$uri = substr($uri, 0, $pos);
		}
		
		/*if(($pos = strrpos($uri, '.')) !== false)
		{
			$uri = substr($uri, $pos+1);
		}
		else if(substr($uri, -1, 1) == '/')
		{
			$uri = '/';
		}*/
		
		$expires = 0;
		
        if($client_cache = THttpRouting::getClientCacheConfig())
        { 
            $expires = $client_cache['expires'];
            
            if(isset($client_cache['etag-session-sensitive']))
            {
                self::$_session_sensitive = TVar::toBool($client_cache['etag-session-sensitive']) && THttpRequest::User()->isLoggedIn();
            }
        }
        else
        {
		     $config = Engine::GetConfig('/caching/client-side/url', Engine::SITECONFIG);
				
    		 foreach($config as $group)
	       	 {
			     $group['match'] = str_replace('.', '\.', $group['match']);
			
			     if(preg_match('{'.$group['match'].'}i', $uri))
			     {
				      $expires = $group['expires'];
				
				      if(isset($group['etag-session-sensitive']))
				      {
					       self::$_session_sensitive = TVar::toBool($group['etag-session-sensitive']) && THttpRequest::User()->isLoggedIn();
				      }
				      break;
			     }
	       	 }
		}
		
		if($expires)
		{
			$gmt = gmdate('D, d M Y H:i:s', time()).' GMT';
			$exp_gmt = gmdate('D, d M Y H:i:s', time() + $expires).' GMT';
			$type = self::$_session_sensitive ? 'private' : 'public';
			
			THttpResponse::setHeader('Date', $gmt);
			THttpResponse::setHeader('Expires', $exp_gmt);
			THttpResponse::setHeader('Cache-Control', "{$type}, max-age={$expires}");
			THttpResponse::setHeader('Pragma', "max-age={$expires}");
			THttpResponse::setHeader('Vary', 'Etag, Accept-Encoding');
		}
	}
}