<?php
ini_set('date.timezone', 'Europe/Warsaw');

include 'Framework'.DIRECTORY_SEPARATOR.'Engine.class.php';

Engine::Using('System.Autoloader');
Engine::Using('System.Application');

$application = TApplication::getInstance();
$application->Execute(); 