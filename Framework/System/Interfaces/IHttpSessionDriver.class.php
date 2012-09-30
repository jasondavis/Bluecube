<?php
/**
IHttpSessionDriver.class.php

Author: Borys Forytarz
*/

interface IHttpSessionDriver
{

	public function GenerateSid();

	public function GetSid();

	public function Read($key, $default = null);

	public function Write($key, $value);

	public function Start($sid = null);

	public function Destroy();

	public function Clear($key);

	public function Store();
}
