<?php
Engine::Using('System.Object');

/**
 * TExceptionHandler class
 *
 * This class handles all uncaught exceptions
 *
 * 
 *
 */
class TExceptionHandler extends TObject
{
	public function Handle($e)
	{
		header('HTTP/1.1 500 Internal Server Error');
		@ob_end_clean();

		if(Engine::getMode() == Engine::MODE_DEBUG && class_exists('TExceptionDisplay', false))
		{
			TExceptionDisplay::DisplayException($e);
		}
		else
		{
			if(defined('SITE_ROOT') && file_exists(SITE_ROOT.DS.'ErrorPages'.DS.'500.html'))
			{
				$file = SITE_ROOT.DS.'ErrorPages'.DS.'500.html';
			}
			else if(file_exists(ROOT_DIR.DS.'ErrorPages'.DS.'500.html'))
			{
				$file = ROOT_DIR.DS.'ErrorPages'.DS.'500.html';
			}
			else
			{
				$file = false;
			}
			
			if($file)
			{
				readfile($file);
			}
			else
			{
				echo '<html><head><title>Internal Server Error</title></head>
				<body><h1>Internal Server Error</h1>This server encountered an error in web application and is unable to complete your request.
				If you are server administrator, change the engine mode to MODE_DEBUG to see detailed
				information. Otherwise, you may want to <a href="mailto:'.$_SERVER['SERVER_ADMIN'].'">contact administrator</a>.
				<br /><br /><i>'.Engine::IDENTIFIER.' '.Engine::VERSION.'</i></body></html>';
			}
			
			try
			{
				$cfg = Engine::GetConfig('/errors/option[@name="mailto"]', Engine::SITECONFIG);
				
				if(count($cfg) != 0)
				{
					try
					{
						$m = new TEmailMessage;
						$m->AddAddress($cfg[0]['value']);
						$m->Subject = 'Exception '.get_class($e);
						$m->Body = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']."\n\n";
						$m->Body .= (string) $e;
						$m->Send();
					}
					catch(Exception $e)
					{
						echo $e;
					}
				}
			}
			catch(Exception $e)
			{
				
			}
		}
		define('__EXIT_WITH_ERROR__', 1);
		exit;
	}
}

set_exception_handler(array(new TExceptionHandler, 'Handle'));
