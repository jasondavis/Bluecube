<?php
class TSqLiteCacheController extends TObject implements ICacheController
{
	protected $_cache;
	protected $_options = array(
		'gc-probability' => 1,
		'gc-divisor' => 100
	);
	protected $_links = array();

	public function __construct(array $options = array())
	{
		if(!isset($this->_options['storage']))
		{
			$this->_options['storage'] = CACHE_DIR.DS.'Sites'.DS.CURRENT_SITE.DS;
		}

		$this->_options = array_merge($this->_options, $options);

		$dir = dirname($this->_options['storage']);

		if(!is_dir($dir))
		{
			mkdir($dir);
		}
	}
	
	public function __destruct()
	{
		if($this->_cache)
		{
			sqlite_close($this->_cache);
		}
	}
	
	private function _prepareDb($identifier)
	{
		$dir = $this->_options['storage'];
		$filename = $this->_options['storage'].DS.'SQLiteCache.db.cache';

		if(!is_dir($dir))
		{
			mkdir($dir, 0775, true);
		}
		
		if(!$this->_cache)
		{
		    $this->_cache = sqlite_popen($filename, 0775);
		}
		
		static $prepared = array();
		static $cleaned = array();
		
		if(!isset($prepared[$filename]))
		{
    		try
	   	    {
		  	    sqlite_exec('
				    CREATE TABLE cache
				    (
			            identifier TEXT PRIMARY KEY,
    					content BLOB,
	       				expires INTEGER KEY
				    )
			    ', $this->_cache);
		     }
		     catch(Exception $e)
		     {
			     
		     }
		     
		     $prepared[$filename] = true;
		}
		
		if(!isset($cleaned[$filename]) && rand($this->_options['gc-probability'], $this->_options['gc-divisor']) == $this->_options['gc-probability'])
		{
			$now = time();
		
			sqlite_exec("
				DELETE FROM cache
				WHERE (expires > 0 AND expires < $now)
			", $this->_cache);
			
			$cleaned[$filename] = true;
		}
		
		return sqlite_escape_string($identifier);
	}

	public function Write($identifier, $content, $expires = 0)
	{
		$identifier = $this->_prepareDb($identifier);

		$content = sqlite_escape_string(TObject::Serialize($content));
		$expires = $expires > 0 ? time() + $expires : 0;
		
		try
		{
			sqlite_exec("
				DELETE FROM cache WHERE identifier = '$identifier'
			", $this->_cache);
			
			sqlite_exec("
				INSERT INTO cache
				(
					identifier,
					content,
					expires
				)
				VALUES
				(
					'$identifier',
					'$content',
					$expires
				)
			", $this->_cache);
					
			return true;
		}
		catch(Exception $e)
		{
			return false;
		}
	}

	public function Read($identifier)
	{
		$expires = time();
		$identifier = $this->_prepareDb($identifier);
		
		$res = sqlite_query("
			SELECT content
			FROM cache
			WHERE (expires = 0 OR expires >= $expires)
			AND identifier = '$identifier'
			LIMIT 1
		", $this->_cache);
		
		if(sqlite_num_rows($res) == 0 || !($data = sqlite_fetch_single($res)))
		{
			return false;
		}
		
		return TObject::Unserialize($data);
	}

	public function Delete($identifier)
	{
		$identifier = $this->_prepareDb($identifier);
		
		sqlite_exec("
			DELETE FROM cache
			WHERE identifier = '$identifier'
		", $this->_cache);
		
		return sqlite_changes($this->_cache) > 0;
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
		sqlite_exec("
			DELETE FROM cache
		", $this->_cache);
		
		return true;
	}
	
	public function CleanExpired()
	{
		return true;
	}
}