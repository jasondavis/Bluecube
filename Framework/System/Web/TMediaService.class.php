<?php
Engine::Using('System.Http.HttpResponse');
Engine::Using('System.Web.Service');
/**
 * This simple service is intended to serve images, css stylesheets and
 * javascript files from their directories from within the requested site
 * directory (according to requested host name).
 * 
 * If you want to increase the performance, you should use mod_rewrite in
 * Apache to serve these files directly instead using this service. 
 * 
 * IMPORTANT NOTES: 
 * 
 * 1. This service is automatically executed by the framework during each
 * request to the following directories: /Assets, /Images, /Styles, /Scripts
 * 
 * 2. Access to this service COULD NOT be disabled using authorization manager
 * 
 * 3. To change default behavior of this service or create access rules, create
 * a new service (by extending this class) and then define required routing
 * rules and/or access rules.
 * 
 * 
 */

class TMediaService extends TService
{
	private $path;
	private $types = array(
		'js' => 'text/javascript',
		'css' => 'text/css',
		'txt' => 'text/plain',
		'gif' => 'image/gif',
		'jpg' => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'jpe' => 'image/jpeg',
		'png' => 'image/png',
		'xml' => 'text/xml',
		'html' => 'text/html',
		'htm' => 'text/html',
	    'ico' => 'image/x-icon'
	);
	
	public function GetImage()
	{
		$this->ServeFrom(SITE_ROOT.DS.'Images'.DS.$this->path);
	}
	
	public function GetScript()
	{
		$this->ServeFrom(SITE_ROOT.DS.'Scripts'.DS.$this->path);
	}
	
	public function GetStyle()
	{
		$this->ServeFrom(SITE_ROOT.DS.'Styles'.DS.$this->path);
	}
	
	public function GetAsset()
	{
		$this->ServeFrom(FRAMEWORK_DIR.DS.'Assets'.DS.$this->path);
	}
	
	protected function Service_Init()
	{
		$this->path = THttpRequest::Get('path');
		$this->path = str_replace('../', '', $this->path);
		$this->path = str_replace('/', DS, $this->path);
	}
	
	protected function CheckIfModified($path)
	{		
		$last_modified_time = filemtime($path);
		$etag = md5_file($path);

		THttpCache::CheckModified($last_modified_time);
		THttpCache::CheckEtag($etag);
	}
	
	protected function ServeFrom($localpath)
	{
		if(!file_exists($localpath)) throw new ResponseError(404);
		
		$this->CheckIfModified($localpath);
		
		$ext = strtolower(substr($this->path, strrpos($this->path, '.')+1));

		if(isset($this->types[$ext]))
		{
			THttpResponse::setHeader('Content-Type', $this->types[$ext]);
		}
		
		THttpResponse::setHeader('Content-Length', filesize($localpath));
		
		readfile($localpath);
		exit;
	}
}