<?php
/**
 * THttpCookie class represents HTTP Cookie
 * 
 * 
 */

//@TODO: Complete the cookie class

class THttpCookie extends TObject
{
	protected
		$_name,
		$_value,
		$_expires = 0,
		$_domain,
		$_path,
		$_secure = false,
		$_httpOnly = false
	;
	
	public function __construct($name, $value, $expires = 0, $path = null, $domain = null, $secure = false, $httpOnly = true)
	{
		$this->_name = $name;
		$this->_value = $value;
		$this->_expires = $expires;
		$this->_domain = $domain;
		$this->_path = $path;
		$this->_secure = $secure;
		$this->_httpOnly = $httpOnly;
	}
	
	public function setName($name)
	{
		$this->_name = $name;
	}
	
	public function setValue($value)
	{
		$this->_value = $value;
	}
	
	public function setExpires($expires)
	{
		$this->_expires = $expires;
	}
	
	public function setDomain($domain)
	{
		$this->_domain = $domain;
	}
	
	public function setPath($path)
	{
		$this->_path = $path;
	}
	
	public function setSecure($secure)
	{
		$this->_secure = TVar::toBool($secure);
	}
	
	public function setHttpOnly($httpOnly)
	{
		$this->_httpOnly = TVar::toBool($httpOnly);
	}
	
	public function __toString()
	{
		return 'Set-Cookie: '
			.urlencode($this->_name).'='.urlencode($this->_value)
			.(empty($this->_domain) ? '' : '; Domain='.$this->_domain)
			.(empty($this->_expires) ? '' : '; Max-Age='.$this->_expires)
			.(empty($this->_path) ? '' : '; Path='.$this->_path)
			.(!$this->_secure ? '' : '; Secure')
			.(!$this->_httpOnly ? '' : '; HttpOnly')
		;
	}
}