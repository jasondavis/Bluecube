<?php
/**
 * TExceptionDisplay class
 *
 * This class, if loaded, displays exceptions and debugging information.
 * This extension is loaded automatically if engine's mode is MODE_DEBUG.
 *
 * 
 *
 */
class TExceptionDisplay extends TObject
{
	public static function DisplayException(Exception $e)
	{
		$stack = explode("\n", $e->getTraceAsString());
		
		$stackStr = '<div class="stack-trace">'.implode('</div><div class="stack-trace">', $stack).'</div>';
		$stackStr = str_replace(ROOT_DIR,'',$stackStr);
		
		$sourceLines = file($e->getFile());
		$numLines = count($sourceLines);
		
		$offset = $e->getLine()-10;
		$length = 20;
		
		if($offset < 0) $offset = 0;
		
		$sourceLines = array_slice($sourceLines, $offset, $length, true);
		$source = '';
		
		foreach($sourceLines as $lineNo => $line)
		{
			$css = $lineNo%2 == 0 ? 'line-1' : 'line-2';
			$css = $lineNo+1 == $e->getLine() ? 'error-line' : $css;
			
			$line = str_replace('   ', "\t", $line); //4 spaces => 1 tab
			$line = str_replace("\t",'&nbsp;&nbsp;&nbsp;&nbsp;', htmlspecialchars($line)); //1 tab => 4 nbsps
			
			$source .= '<div class="'.$css.'">'.$line.'</div>';
		}
		
		$title = defined('CURRENT_SITE') ? ('Error in \''.CURRENT_SITE.'\' site') : ('Application error');
		
		@ob_end_clean();
		
		echo '<html><head>
		<meta name="content-type" content="text/html; charset=UTF-8" />
		<title>'.$title.'</title>
		<style type="text/css">
			h1 {color:red; font-weight:normal;margin-top:0}
			h2 {font-weight:normal}
			body {font-family:verdana,helvetica,arial; font-size:15px;background-color:#F5F5F5}
			div.footer {margin-top:30px;font-size:10px;color:gray;border-top:solid silver 1px}
			div.line-1, div.line-2, div.error-line, div.stack-trace {font-size:12px;padding:3px}
			div.error-line {color:red;background-color:rgb(255,224,224)}
			div.error-panel {border:solid silver 1px;padding:10px;background-color:white}
			div.error-container {margin:20px}
			div.source-container {background-color:rgb(255,255,245);border:dashed rgb(212,212,138) 1px;padding:5px}
			/*div.line-1 {background-color:rgb(255,255,232)}*/
		</style>
		</head>
		
		<body>

			<div class="error-container">
				<div class="error-panel">
					<h1>'.$title.'</h1>
					
					<h2>Uncaught exception '.get_class($e).': <b>'.htmlspecialchars($e->getMessage()).'</b></h2>
			
					<p>Thrown in <b>'.preg_replace('/^'.preg_quote(ROOT_DIR, '/').'/', '', $e->getFile()).'</b> on line <b>'.$e->getLine().'</b></p>
			
					<p><b>Source preview:</b></p>
					<div class="source-container">			
					'.$source.'
					</div>

					<p><b>Stack trace:</b></p>
			
					<div class="stack-trace">
					'.$stackStr.'
					</div>
			
					<div class="footer">
					'.Engine::IDENTIFIER.' '.Engine::VERSION.' on
					'.$_SERVER['SERVER_SOFTWARE'].' / PHP '.PHP_VERSION.'
					</div>
				</div>			
			</div>
		</body>
		</html>
		';
		exit;
	}
}
