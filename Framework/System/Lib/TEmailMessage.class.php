<?php
/**
 * 
 */
class TEmailMessage extends PHPMailer
{
	public function __construct($serverName = null)
	{
		if($serverName == null)
		{
			$default = Engine::GetConfig('mailing/option[@name="default_sender"]', Engine::SITECONFIG);
			$serverName = $default[0]['value'];
		}
		
		$options = Engine::GetConfig('mailing/sender[@name="'.$serverName.'"]', Engine::SITECONFIG);
		
		$this->CharSet = 'utf-8';
		
		$this->Host = isset($options[0]['host']) ? $options[0]['host'] : 'localhost';
		
		$this->Port = isset($options[0]['port']) ? TVar::toInt($options[0]['port']) : 25;
		
		$this->Mailer = isset($options[0]['mailer']) ? $options[0]['mailer'] : 'smtp';

		$this->SMTPSecurity = isset($options[0]['smtpsecurity']) ? $options[0]['smtpsecurity'] : ''; 
		
		$this->Username = $options[0]['username'];
		
		$this->Password = $options[0]['password'];
		
		$this->SMTPAuth = isset($options[0]['smtpauth']) ? TVar::toBool($options[0]['smtpauth']) : false;
		
		$this->From = $options[0]['from'];
		
		$this->FromName = $options[0]['fromname'];
		
		$this->Timeout = isset($options[0]['timeout']) ? TVar::toInt($options[0]['timeout']) : 10;
		
		$this->Sendmail = isset($options[0]['sendmail']) ? $options[0]['sendmail'] : '/usr/sbin/sendmail';
	}

	public function Send()
	{
		$ret = parent::Send();

		$this->ClearReplyTos();
		$this->ClearAllRecipients();
		$this->ClearAttachments();
		$this->ClearCustomHeaders();

		return $ret;
	}
}