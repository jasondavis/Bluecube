<?php
/**
 * TFileCacheController
 *
 * Cache controller based on files
 *
 *
 * @TODO Complete the controller
 */

class TFileCacheController extends TObject implements ICacheController
{
	protected $_options = array();

	public function __construct(array $options = array())
	{
		$this->_options = array(
			'storage' => CACHE_DIR.DS.'Sites'.DS.CURRENT_SITE.DS.'Data'
		);

		$this->_options = array_merge($this->_options, $options);

		if(!is_dir($this->_options['storage']))
		{
			mkdir($this->_options['storage'], 0775, true);
		}
	}

	private function _getPath($identifier)
	{
		$identifier = str_replace(':', DS, $identifier);
		$identifier = str_replace('..'.DS, DS, $identifier);

		$dir = $this->_options['storage'].DS.dirname($identifier);

		if(!is_dir($dir))
		{
			mkdir($dir, 0775, true);
		}

		return $this->_options['storage'].DS.$identifier.'.cache';
	}

	public function Write($identifier, $content, $expires = 0)
	{
		$filename = $this->_getPath($identifier);

		return file_put_contents($filename, TObject::Serialize(
			array(
				'data' => $content,
				'expires' => $expires > 0 ? time() + $expires : 0
			)
		), LOCK_EX);
	}

	public function Read($identifier)
	{
		$filename = $this->_getPath($identifier);

		if(file_exists($filename) && ($content = @file_get_contents($filename, LOCK_EX)))
		{
			if(!($data = TObject::Unserialize($content)))
			{
				return false;
			}

			if($data['expires'] == 0 || $data['expires'] >= time())
			{
				return $data['data'];
			}
			else
			{
				@unlink($filename);
			}
		}

		return false;
	}

	public function Delete($identifier)
	{
		$filename = $this->_getPath($identifier);

		if(file_exists($filename) && is_file($filename))
		{
			return @unlink($filename);
		}

		return false;
	}

	public function Evaluate($identifier)
	{
		if($data = $this->Read($identifier))
		{
			$data = str_replace(array('<?php', '<?', '?>'), '', $data);
			return eval($data);
		}

		return false;
	}

	public function Clean()
	{
		return $this->_cleanCacheDir($this->_options['storage']);
	}
	
	public function CleanExpired()
	{
		return true;
	}
	
	private function _cleanCacheDir($dir)
	{
		try
		{
			$d = opendir($dir);
			
			while($f = readdir($d))
			{
				if($f == '.' || $f == '..') continue;
				
				if(is_dir($dir.DS.$f))
				{
					if(!$this->_cleanCacheDir($dir.DS.$f))
					{
						closedir($d);
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