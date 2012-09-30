<?php
/**
 * 
 */
Engine::Using('System.Object');
Engine::Using('System.Lib.Var');

class TUser extends TObject
{
	private $data = array();
	
	public function __construct(array $data)
	{
		$this->data = $data;
	}
	
	public function getData($key)
	{
		if(!isset($this->data[$key])) return false;
		
		return $this->data[$key];
	}
	
	public function getUserKey()
	{
		return md5(serialize($this));
	}
	
	public function hasRole($role, $allRoles = false)
	{
		if($roles = $this->getData('roles'))
		{
			$roles = TVar::toArray($roles);
			
			if(is_array($role)) //list of roles given
			{
				if($allRoles) //user must have all roles
				{
					foreach($role as $r)
					{
						if(!in_array($r, $roles)) return false;
					}
					
					return true;
				}
				else //user must have at least one role
				{
					foreach($role as $r)
					{
						if(in_array($r, $roles)) return true;
					}
					
					return false;
				}
			}
			else //one role given
			{
				return in_array($role, $roles);
			}
		}
		
		return false;
	}
	
	public function isLoggedIn()
	{
		if(isset($this->data['uid'])) return $this->data['uid'] > 0;
		
		return false;
	}
}
