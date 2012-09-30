<?php
/**
 * 
 */
class THyperLink extends TControl
{
	public function getTagName()
	{
		return 'a';
	}
	
	public function setHref($href)
	{
		$this->setNavigateUrl($href);
	}
	
	public function getHref()
	{
		return $this->getNavigateUrl();
	}
	
	public function setNavigateUrl($url)
	{
	    if(is_string($url))
	    {
	    	if(preg_match_all('{^\s*\[([^\[]*)\]\s*$}', $url, $matches))
		    {
		        $parts = preg_split('{\s+}', $matches[1][0]);
		        
		        $url = array();
		        
		        foreach($parts as $part)
		        {
		            $e = explode('=', $part, 2);
		            
		            if(isset($e[1]))
		            {
		                $url[$e[0]] = $e[1];
		            }
		        }
		    }
	    }
	    
		if(is_array($url))
		{
			$page		= isset($url['page'])		?	$url['page']	: '';
			$action		= isset($url['action'])		?	$url['action']	: '';
			
			if(!TAuthManager::CheckAuth($page, $action, true))
			{
				$this->AddCssClass('auth-required');
				//return;
			}
			
			$url = new THttpUrl($url);
		}
		
		if($url instanceOf THttpUrl)
		{
			$url = (string) $url;
		}
		
		$this->setViewState('NavigateUrl', $url);
		$this->setAttributeToRender('href', $url);
	}
	
	public function getNavigateUrl()
	{
		return $this->getViewState('NavigateUrl','');
	}
}
