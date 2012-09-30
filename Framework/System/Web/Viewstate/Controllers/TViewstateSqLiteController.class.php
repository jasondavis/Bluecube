<?php
class TViewstateSqLiteController extends TObject implements IViewstateController
{
	protected $_options = array();
	protected $_db;
	protected $_identifier;
	
	public function __construct(array $options = array(), $identifier = null)
	{
		$this->_options = array(
			'storage' => CACHE_DIR.DS.'Sites'.DS.CURRENT_SITE.DS.'Data'.DS.'Viewstate.db.cache',
			'expires' => 3600
		);
		
		$this->_identifier = $identifier;

		$this->_options = array_merge($this->_options, $options);

		$dir = dirname($this->_options['storage']);

		if(!is_dir($dir))
		{
			mkdir($dir);
		}

		$this->_db = sqlite_popen($this->_options['storage'], 0775);
		
		try
		{
			sqlite_exec('
				CREATE TABLE viewstate
				(
					identifier TEXT PRIMARY KEY,
					content BLOB,
					expires INTEGER KEY
				)
			', $this->_db);
		}
		catch(Exception $e)
		{
			
		}
	}
	
	private function __createIdentifier()
	{
		sqlite_exec('
			DELETE FROM viewstate WHERE expires < '.time()
		, $this->_db);
		
		
		if(isset($_SESSION['__system']['viewstate_id']) && empty($_POST))
		{
			$identifier = $_SESSION['__system']['viewstate_id'];
		}
		else
		{
			$identifier = str_replace('=', '', base64_encode(session_id().':'.sha1($_SERVER['REMOTE_ADDR'])));
			
			while(sqlite_num_rows(sqlite_query("SELECT identifier FROM viewstate WHERE identifier = '$identifier' LIMIT 1", $this->_db)) > 0)
			{
				$identifier = str_replace('=', '', base64_encode(sha1(microtime(true)).':'.sha1($_SERVER['REMOTE_ADDR'])));
			}
			
			$_SESSION['__system']['viewstate_id'] = $identifier;
		}
		
		
		/*do
		{
			$identifier = str_replace('=', '', base64_encode(sha1(microtime(true)).':'.sha1($_SERVER['REMOTE_ADDR'])));
		}
		while(sqlite_num_rows(sqlite_query("SELECT identifier FROM viewstate WHERE identifier = '$identifier' LIMIT 1", $this->_db)) > 0);*/
		
		return $identifier;
	}
	
	public function Write($data)
	{
		if(!$this->_identifier)
		{
			$this->_identifier = $this->__createIdentifier();
		}
		
		$content = sqlite_escape_string(TObject::Serialize($data));
		$identifier = sqlite_escape_string($this->_identifier);
		$expires = time() + $this->_options['expires'];
		
		sqlite_exec("
			DELETE FROM viewstate WHERE identifier = '$identifier' 
		", $this->_db);
		
		$this->_identifier = $this->__createIdentifier();
		$identifier = sqlite_escape_string($this->_identifier);
		
		sqlite_exec("
			INSERT INTO viewstate
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
		", $this->_db);
		
		return $this->_identifier;
	}
	
	public function Read()
	{
		$identifier = sqlite_escape_string($this->_identifier);
		$expires = time();
		
		$data = sqlite_query("
			SELECT content FROM viewstate WHERE
			identifier = '$identifier'
		", $this->_db);
		
		if(sqlite_num_rows($data) == 0)
		{
			return array();
		}
		
		return TObject::Unserialize(sqlite_fetch_single($data));
	}
}