<?php
/**
 * 
 */
class TEmailAddressValidator extends TRegularExpressionValidator
{
	protected function onCreate(TEventArgs $e)
	{
		$this->setRegularExpression('^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4}){1}$');
		parent::onCreate($e);
	}
}
?>