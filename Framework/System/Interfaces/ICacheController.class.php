<?php
interface ICacheController
{
	public function Write($content, $identifier, $expires = 0);
	public function Read($identifier);
	public function Delete($identifier);
	public function Evaluate($identifier);
	public function Clean();
	public function CleanExpired();
}