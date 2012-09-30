<?php
/**
 * 
 */
class THiddenField extends TFormControl
{
    protected function onCreate(TEventArgs $args)
    {
        parent::onCreate($args);
        
        if(!empty($_POST))
        {
            $name = $this->getName();
            
            $this->setText(isset($_POST[$name]) ? $_POST[$name] : $this->getViewState('text'));
        }
    }
    
	public function getTagName()
	{
		return 'input';
	}
	
	public function getAllowChildControls()
	{
		return false;
	}
	
	public function getHasEndTag()
	{
		return false;
	}
	
	public function getText()
	{
		return $this->getViewState('text');
	}
	
	public function setText($text)
	{
		$this->setViewState('text', $text);
	}
	
	protected function onRender(TEventArgs $args)
	{
		$this->setAttributeToRender('type','hidden');
		$this->setAttributeToRender('value', $this->getViewState('text'));
	}
}
