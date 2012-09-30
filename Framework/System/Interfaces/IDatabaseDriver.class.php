<?php
/**
IDatabaseDriver.class.php

Author: Borys Forytarz
*/

interface IDatabaseDriver
{

	public function __construct(TDatabaseConnection $connection = null);

	public function Query($query, array $params = array());

	public function LastInsertId();
}
