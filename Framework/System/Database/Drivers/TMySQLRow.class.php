<?php
/**
 * 
 */
class TMySQLRow extends ArrayObject implements IDatabaseRow
{

	public function Keys()
	{
		$keys = array();

		foreach($this as $k => $v)
		{
			$keys[] = $k;
		}

		return $keys;
	}

	public function Values()
	{
		$vals = array();

		foreach($this as $v)
		{
			$vals[] = $v;
		}

		return $vals;
	}

	public function ToArray()
	{
		$ret = array();
	}
}