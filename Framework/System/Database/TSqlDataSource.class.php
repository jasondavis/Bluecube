<?php
/**
TSqlDataSource.class.php

Author: Borys Forytarz
*/

class TSqlDataSource extends TDataSource
{
	public function __construct(IDatabaseResult $result)
	{
		$data = array();
		
		foreach($result as $row)
		{
			$dataRow = array();
			
			foreach($row as $field => $value)
			{
				$dataRow[$field] = $value;					
			}
			
			$data[] = $dataRow;
		}
		
		parent::__construct($data);
	}
}
