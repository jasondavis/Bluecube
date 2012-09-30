<?php
/**
 * 
 */
define('START_TIME', microtime(true));

class TDebugInfo extends TObject
{
	public static function getExecTime()
	{
		return microtime(true) - START_TIME;
	}
	
	public static function GenerateDebugScript()
	{
		//Constant __EXIT_WITH_ERROR__ is getting defined only when an error caused application to terminate
		
		if(defined('__EXIT_WITH_ERROR__')) exit;

		if(defined('__DONT_SHOW_DEBUG_INFO__')) return;
		
		$memory = memory_get_usage(false) / 1024;
		
		$script = "
					<script type=\"text/javascript\">
						document.write('<div style=\"border-left:solid silver 1px;border-bottom:solid silver 1px;font-family:verdana,helvetica,arial;background-color:#F5F5F5;position:absolute;z-index:100000000;right:0;top:0\">');
						document.write('<table style=\"font-size:13px;\"><tr>');
						document.write('<td style=\"padding-right:20px\">v. ".Engine::VERSION."</td>');
						document.write('<td style=\"padding-right:20px\">".round(self::getExecTime()*1000, 3)." ms.</td>');
						document.write('<td>".round($memory, 3)." KB</td>');
						document.write('</tr></table>');
						document.write('</div>');
					</script>
		";
		
		echo $script;
	}
}

register_shutdown_function(array('TDebugInfo', 'GenerateDebugScript'));
