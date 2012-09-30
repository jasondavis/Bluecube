<?php
/**
 * 
 */
Engine::Using('System.Web.Controls.TemplateControl');
Engine::Using('System.Security.AuthManager');

class TAuthForm extends TTemplateControl
{
	protected function Authorize()
	{
		if(!TAuthManager::Authorize($this->Auth_Username->Text, $this->Auth_Password->Text))
		{
			$this->RaiseEvent('onAuthFail');
		}
		else
		{
			$this->RaiseEvent('onAuthSuccess');
		}
	}
	
	public function setUsernameLabel($label)
	{
		$this->Auth_UsernameLabel->Text = $label;
	}
	
	public function setPasswordLabel($label)
	{
		$this->Auth_PasswordLabel->Text = $label;
	}
	
	public function setButtonLabel($label)
	{
		$this->Auth_Button->Text = $label;
	}
	
	public function getUsernameLabel()
	{
		return $this->Auth_UsernameLabel->Text;
	}
	
	public function getPasswordLabel()
	{
		return $this->Auth_PasswordLabel->Text;
	}
	
	public function getButtonLabel()
	{
		return $this->Auth_Button->Text;
	}
	
	protected function onAuthFail(TEventArgs $e) {}
	protected function onAuthSuccess(TEventArgs $e) {}
}