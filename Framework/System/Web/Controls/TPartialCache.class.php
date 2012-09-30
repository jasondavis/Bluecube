<?php
/**
 * 
 */
class TPartialCache extends TControl
{
	private $_expires = 60;
	private $_parameters = array();
	private $_session_sensitive = true;
	private $_request_sensitive = true;
	private $_page_sensitive = true;
	private $_check_result = false;
	
	public function getTagName()
	{
		return null;
	}
	
	public function AddParameter($name, $value)
	{
		$this->_parameters[$name] = $value;
	}
	
	public function setSessionSensitive($bool)
	{
		$this->_session_sensitive = TVar::toBool($bool);
	}
	
	public function setRequestSensitive($bool)
	{
		$this->_request_sensitive = TVar::toBool($bool);
	}
	
	public function setPageSensitive($bool)
	{
		$this->_page_sensitive = TVar::toBool($bool);
	}
	
	public function setExpires($expires)
	{
		$this->_expires = (int) $expires;
	}
	
	public function getExpires()
	{
		return $this->_expires;
	}
	
	private function _prepareKey()
	{
		$key = 'Partials:';
		$key .= $this->_page_sensitive ? (get_class($this->getPage()).':') : 'Default:';
		$key .= $this->getClientId().':';
		$key .= $this->_request_sensitive ? (THttpRequest::getHash($this->_session_sensitive).':') : '';
		$key .= md5(var_export($this->_parameters, true));
		
		return $key;
	}
	
	public function IsCached()
	{
		$this->_check_result = TCache::Read($this->_prepareKey());
		
		return $this->_check_result && $this->_check_result != '' ? true : false;
	}
	
	public function RenderContent()
	{
		$key = $this->_prepareKey();

		if(($cached = $this->_check_result) || ($cached = TCache::Read($key)))
		{
			echo $cached;
		}
		else
		{
			if($this->_expires > -1)
			{
				ob_start();
				parent::RenderContent();
				$content = ob_get_clean();
			
				if(Engine::getMode() == Engine::MODE_PERFORMANCE)
				{
				    TCache::Write($key, $content, $this->_expires);
				}
			
				echo $content;
			}
			else
			{
				parent::RenderContent();
			}
		}
	}
}
