<?php
class Home extends Master
{
    protected function Page_Init() 
    {
        
    }
    
    protected function Page_Load()
    {

    }
	
    protected function Button_Click($sender, $event)
    {
        $sender->Text = 'You clicked me!';
        $this->TextBox->Text = 'You clicked the button!';
        $this->ActionLink->Show();
    }
	
    public function action1()
    {
        $this->SomeText->Text = 'Hello from action1!';
    }
}