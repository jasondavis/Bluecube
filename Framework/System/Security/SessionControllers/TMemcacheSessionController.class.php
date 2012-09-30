<?php
/**
 * 
 */
class TMemcacheSessionController extends TObject implements ISessionController
{
	protected $memcache;
	protected $lifetime = 0;
	
	public function open($save_path, $session_name)
	{
		$this->memcache = new TMemcache;
		
		$session_config = Engine::GetConfig('/authorization/session', Engine::SITECONFIG);
		
		foreach($session_config[0] as $key => $opt)
		{
			if($key == 'lifetime')
			{
				$this->lifetime = $opt;
				break;
			}
		}

		return true;
	}
	
	public function close()
	{
		return true;
	}
	
	public function read($session_id)
	{
		return $this->memcache->get('__session_'.$session_id);
	}
	
	public function write($session_id, $value)
	{
		$this->memcache->set('__session_'.$session_id, $value, false, $this->lifetime);
		
		return true;
	}
	
	public function destroy($session_id)
	{
		$this->memcache->delete('__session_'.$session_id);
		
		return true;
	}
	
	public function clean()
	{
		return true;
	}
}