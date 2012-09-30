<?php
/**
 *
 */
class TMySQLResult extends TObject implements IDatabaseResult
{
	protected $_data;
	protected $_row = 0;
	protected $_query;
	protected $_link;
	protected $_total = 0;
	protected $_count = -1;

	public function __construct(&$result, $query = null, $link = null)
	{
		$this->_data = $result;
		$this->_query = $query;
		$this->_link = $link;
		
		if(preg_match('{^SELECT\s+SQL_CALC_FOUND_ROWS\s+}i', $query))
		{
			$data = mysql_fetch_assoc(mysql_query('SELECT FOUND_ROWS() as `count`', $link));
			
			$this->_total = $data['count'];
		}
	}
	
	public function CountTotal()
	{
		return $this->_total;
	}
	
	public function toArray()
	{
		$ret = array();
		
		foreach($this as $item)
		{
			$ret[] = $item;
		}
		
		return $ret;
	}

	public function __sleep() //while serializing, change the $_data from mysql result resource into array of records
	{
		$data = array();

		foreach($this as $item)
		{
			$data[] = $item;
		}

		$this->_data = $data;

		return array('_data', '_row', '_query', '_total');
	}

	public function GetQuery()
	{
		return $this->_query;
	}

	public function GetResource()
	{
		return $this->_data;
	}

	public function OffsetExists($offset)
	{
		if(is_array($this->_data))
		{
			if(is_integer($offset))
			{
				$rows = count($this->_data);

				return $rows > 0 && $offset >= 0 && $offset < $rows;
			}
			else if(is_string($offset))
			{
				return isset($this->_data[$this->_row][$offset]);
			}
			else throw new InvalidOperationException('Offset must be integer or string');
		}
		else
		{
			if(!is_resource($this->_data)) throw new InvalidOperationException('Result type does not allow this operation');

			if(is_integer($offset))
			{
				$rows = $this->count();

				return $rows > 0 && $offset >= 0 && $offset < $rows;
			}
			else if(is_string($offset))
			{
				mysql_data_seek($this->_data, $this->_row);
				$c = mysql_fetch_assoc($this->_data);
				mysql_data_seek($this->_data, $this->_row);

				return isset($c[$offset]);
			}
			else throw new InvalidOperationException('Offset must be integer or string');
		}
	}

	public function OffsetGet($offset)
	{
		if($this->count() == 0) throw new InvalidOperationException('Result is empty');

		if(is_array($this->_data))
		{
			if(is_integer($offset))
			{
				return new TMySQLRow($this->_data[$offset]);
			}
			else if(is_string($offset))
			{
				$col = array();

				foreach($this->_data as $assoc)
				{
					$col[] = &$assoc[$offset];
				}

				return new TMySQLRow($col);
			}
			else throw new InvalidOperationException('Offset must be integer or string');
		}
		else
		{
			if(!is_resource($this->_data)) throw new InvalidOperationException('Result type does not allow this operation');

			if(is_integer($offset))
			{
				mysql_data_seek($this->_data, $offset);
				return new TMySQLRow(mysql_fetch_assoc($this->_data));
			}
			else if(is_string($offset))
			{
				$col = array();

				while($assoc = mysql_fetch_assoc($this->_data))
				{
					$col[] = &$assoc[$offset];
				}

				mysql_data_seek($this->_data, $this->_row);

				return new TMySQLRow($col);
			}
			else throw new InvalidOperationException('Offset must be integer or string');
		}
	}

	public function OffsetSet($offset, $value)
	{
		throw new InvalidOperationException('Result does not allow this operation');
	}

	public function OffsetUnset($offset)
	{
		throw new InvalidOperationException('Result does not allow this operation');
	}

	public function Count()
	{
		if($this->_count == -1)
		{
		    if(is_array($this->_data)) 
		    {
		    	$this->_count = count($this->_data); 
		    }
		    else if(is_resource($this->_data)) 
		    {
		        $this->_count = mysql_num_rows($this->_data);	
		    }
		    else $this->_count = $this->_data;
		}

		return $this->_count;
	}

	public function Current()
	{
		if(!is_resource($this->_data) && !is_array($this->_data)) throw new InvalidOperationException('Result type does not allow this operation');

		return $this->OffsetGet($this->_row);
	}

	public function Next()
	{
		if(!is_resource($this->_data) && !is_array($this->_data)) throw new InvalidOperationException('Result type does not allow this operation');

		return $this->_row++;
	}

	public function Key()
	{
		if(!is_resource($this->_data) && !is_array($this->_data)) throw new InvalidOperationException('Result type does not allow this operation');

		return $this->_row;
	}

	public function Valid()
	{
		if(!is_resource($this->_data) && !is_array($this->_data)) throw new InvalidOperationException('Result type does not allow this operation');

		return $this->_row < $this->Count();
	}

	public function Rewind()
	{
		if(!is_resource($this->_data) && !is_array($this->_data)) throw new InvalidOperationException('Result type does not allow this operation');

		if($this->count() > 0)
		{
			$this->_row = 0;

			if(is_resource($this->_data)) mysql_data_seek($this->_data, 0);
		}
	}
}