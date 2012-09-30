<?php
/**
 * 
 */
class TTextBox extends TFormControl
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
	
	public function setText($text)
	{
		$this->setViewState('text', $text);
	}
	
	public function getText()
	{
		return $this->getViewState('text');
	}
	
	public function setTextMode($textmode)
	{
		$this->setViewState('textmode', strtolower($textmode));
	}
	
	public function getTextMode()
	{
		return $this->getViewState('textmode', 'singleline');
	}
	
	public function getHasEndTag()
	{
		switch($this->getTextMode())
		{
			case 'singleline':
			case 'password':
				return false;
			case 'multiline':
				return true;
			default:
				throw new InvalidArgumentException($this->getTextMode().' is not recoginized as a valid value of TextMode property');
		}
	}
	
	public function getTagName()
	{	
		switch($this->getTextMode())
		{
			case 'singleline':
			case 'password':
				return 'input';
			case 'multiline':
				return 'textarea';
			default:
				throw new InvalidArgumentException($this->getTextMode().' is not recoginized as a valid value of TextMode property');
		}
	}
	
	public function onRender(TEventArgs $args)
	{
		if($this->getTextMode() == 'singleline')
		{
			$this->setAttributeToRender('value', $this->getText());
		}
		else if($this->getTextMode() == 'password')
		{
			$this->setAttributeToRender('type', 'password');
		}
	}
	
	public function RenderContent()
	{
		if($this->getTextMode() == 'multiline')
		{
			echo htmlspecialchars($this->getText());
		}
	}
}
