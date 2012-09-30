<?php
/**
TDatabaseConnection.class.php

Author: Borys Forytarz
*/

class TDatabaseConnection extends TObject
{
	private $_name = null;
	public $db_host = null;
	public $db_user = null;
	public $db_pass = null;
	public $db_data = null;
	public $db_type = null;
	public $db_charset = null;
	public $pconnect = false;
	
	protected static $_connections = array();

	public function __construct($connection = null)
	{
		if($connection == null)
		{
			$connection = Engine::GetConfig('database/option[@name="default_connection"]', Engine::SITECONFIG);
			
			if(empty($connection)) throw new CoreException('Could not find the `default_connection` option in the `database` section');
			
			$connection = $connection[0]['value'];
		}
		
		$connection_data = Engine::GetConfig('database/connections/connection[@name="'.$connection.'"]', Engine::SITECONFIG);
		
		if(empty($connection_data)) throw new CoreException('Could not find the `'.$connection.'` connection in the `database` section');
		
		$this->_name = $connection_data[0]['name'];
		$this->db_host = $connection_data[0]['host'] ? $connection_data[0]['host'] : ini_get('mysql.default_host');
		$this->db_user = $connection_data[0]['username'] ? $connection_data[0]['username'] : ini_get('mysql.default_user');
		$this->db_pass = $connection_data[0]['password'] ? $connection_data[0]['password'] : ini_get('mysql.default_password');
		$this->db_data = $connection_data[0]['database'];
		$this->db_charset = $connection_data[0]['charset'];
		
		$this->db_type = isset($connection_data[0]['type']) ? $connection_data[0]['type'] : 'mysql';
		$this->pconnect = isset($connection_data[0]['persistent']) ? true : false;
	}

	public function GetLink()
	{
		switch($this->db_type)
		{
			case 'mysql':

				if(isset(self::$_connections[$this->_name]) && mysql_ping(self::$_connections[$this->_name]))
				{
					mysql_select_db($this->db_data, self::$_connections[$this->_name]);
					return self::$_connections[$this->_name];
				}

				$func = $this->pconnect ? 'mysql_pconnect' :'mysql_connect';
				self::$_connections[$this->_name] = $func($this->db_host, $this->db_user, $this->db_pass);
				
				if($this->db_charset) mysql_query("set names '{$this->db_charset}'");
				mysql_select_db($this->db_data, self::$_connections[$this->_name]);
				mysql_query('SET time_zone = "Europe/Warsaw"', self::$_connections[$this->_name]);
				
			break;
			case 'pgsql':

				if(isset(self::$_connections[$this->_name]) && pg_ping(self::$_connections[$this->_name]))
				{
					return self::$_connections[$this->_name];
				}

				$func = $this->pconnect ? 'pg_pconnect' :'pg_connect';
				self::$_connections[$this->_name] = $func("host='{$this->db_host}' user='{$this->db_user}' pass='{$this->db_pass}' dbname='{$this->db_data}'");

			break;
			default:
				throw new CoreException($this->db_type.' is not recognized as supported database type');
			break;
		}

		return self::$_connections[$this->_name];
	}

	public function __toString()
	{
		return md5($this->db_type.$this->db_user.$this->db_pass.$this->db_host.$this->db_data.$this->_name);
	}
}
