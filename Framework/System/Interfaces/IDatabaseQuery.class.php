<?php
/**
IDatabaseQuery.class.php

Author: Borys Forytarz
*/

interface IDatabaseQuery
{

	public function __construct($query = '',array $params = array());

	public function BindParams(array $params);

	public function BindParam($name, $value);

	public function Union($query);

	public function __toString();
}
