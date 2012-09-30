<?php
/**
 * TMemcacheCacheController
 *
 * Cache controller based on Memcache
 *
 *
 * @TODO Complete the controller
 */
class TMemcacheCacheController extends TObject implements ICacheController
{
	protected $_cache;

	public function __construct(array $options = array())
	{
		$this->_cache = new TMemcache;
	}

	public function Write($identifier, $content, $expires = 0)
	{
		$identifier = CURRENT_SITE.':'.$identifier;

		return $this->_cache->set($identifier, $content, false, $expires);
	}

	public function Read($identifier)
	{
		$identifier = CURRENT_SITE.':'.$identifier;

		return $this->_cache->get($identifier);
	}

	public function Delete($identifier)
	{
		$identifier = CURRENT_SITE.':'.$identifier;

		return $this->_cache->delete($identifier);
	}

	public function Evaluate($identifier)
	{
		$identifier = CURRENT_SITE.':'.$identifier;

		if($data = $this->_cache->Read($identifier))
		{
			$data = str_replace(array('<?php', '<?', '?>'), '', $data);
			return eval($data);
		}
	}

	public function Clean()
	{
		$this->_cache->flush();
		
		return true;
	}
	
	public function CleanExpired()
	{
		return true;
	}
}