<?php
/**
 *
 */
class TMySQL extends TObject implements IDatabaseDriver
{
	protected $_conn;

	public function __construct(TDatabaseConnection $conn = null)
	{
		if($conn == null) $conn = new TDatabaseConnection;
		$this->_conn = $conn;
	}
	
	protected function _prepareSql($query, array $params = array())
	{
		if(is_string($query))
		{
			$query = new TMySQLQuery($query, $params);
		}
		
		$query = $query->__toString();
		$query = trim($query);
		
		return $query;
	}

	public function Query($q, array $params = array(), $usecache = true)
	{
		$query = $this->_prepareSql($q, $params);

		$lnk = $this->_conn->GetLink();

		if(!($r = mysql_query($query, $lnk)))
		{
			$errno = mysql_errno($lnk);
			$errstr = mysql_error($lnk);

			throw new DatabaseQueryException($this->Highlight_Error($errno, $errstr, $query));
		}

		if(!is_resource($r)) $r = mysql_affected_rows($lnk);

		return new TMySQLResult($r, $query, $lnk);
	}

	public function LastInsertId()
	{
		return mysql_insert_id($this->_conn->GetLink());
	}

	private function Highlight_Error($errno, $errstr, $query)
	{
		/*$highlight = '<span style="border-bottom:dashed red 1px">\\1</span>';

		switch($errno)
		{
			case 1064:
				if(preg_match_all("{syntax to use near '(.*)' at line}su", $errstr, $matches))
				{
					$query = preg_replace("@(".preg_quote($matches[1][0]).")@", $highlight, $query);
				}
			break;
		}*/

		return "$errno: $errstr: $query";
	}
}
