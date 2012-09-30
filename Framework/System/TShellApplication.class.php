<?php
/**
 * 
 */
Engine::Using('System.Object');
Engine::Using('System.Application');

class TShellApplication extends TObject
{
	
}

function __run()
{
	$app = new TApplication;
	$app->Execute();
}

register_shutdown_function('__run');