<?php
interface IViewstateController
{
	public function __construct(array $options = array(), $identifier = null);
	public function Read();
	public function Write($data);
}