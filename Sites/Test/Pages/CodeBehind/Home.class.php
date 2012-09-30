<?php
class Home extends TPage
{
	protected function Page_Load()
	{

	}

	protected function Button_Click(TButton $sender)
	{
		$sender->Text = 'Hi There! Thanks for clicking!';
	}
}