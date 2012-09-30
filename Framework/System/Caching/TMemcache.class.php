<?php
/**
 * TMemcache class for memcache support. Requires built-in Memcache class.
 * 
 * 
 *
 */
class TMemcache extends Memcache
{
	/**
	 * Constructor. Configures the class according to settings given in configuration
	 * 
	 * @return unknown_type
	 */
	public function __construct()
	{
		$servers = Engine::GetConfig('/memcache/server', Engine::SITECONFIG);
		
		foreach($servers as $server)
		{
			$this->addServer(
				$server['host'],
				TVar::toInt(isset($server['port']))			? $server['port']			: 11211,
				TVar::toBool(isset($server['persistent']))	? $server['persistent']		: true,
				TVar::toInt(isset($server['weight']))		? $server['weight']			: 1,
				TVar::toInt(isset($server['timeout']))		? $server['timeout']		: 1,
				TVar::toInt(isset($server['retry']))		? $server['retry']			: 15,
				TVar::toBool(isset($server['online']))		? $server['online']			: true
			);
		}
	}
	
	/**
	 * Replaces the parent set() implementation. This implementation fixes
	 * an unexpected behavior of original set() while running on environment
	 * with multiple memcache servers.
	 * 
	 * @return bool
	 */
	
	public function set($key, $var, $flag = false, $expire = 0)
	{
		if(!$this->replace($key, $var))
		{
			return parent::set($key, $var, $flag, $expire);
		}
		
		return true;
	}
}