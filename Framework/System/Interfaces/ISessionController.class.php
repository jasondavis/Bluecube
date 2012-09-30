<?php
interface ISessionController
{
	public function open($save_path, $session_name);
	public function close();
	public function read($name);
	public function write($name, $value);
	public function destroy($name);
	public function clean();
}