<?php
/**
 * 
 */
class TFileUpload extends TControl
{
	public function getHasFile()
	{
		return (
			isset($_FILES[$this->getClientId()])
			&& 
			is_uploaded_file($_FILES[$this->getClientId()]['tmp_name'])
			&& 
			$_FILES[$this->getClientId()]['error'] == 0
		);
	}
	
	public function SaveTo($path)
	{
		if(!$this->getHasFile()) return false;
		
		move_uploaded_file($_FILES[$this->getClientId()]['tmp_name'], $path);
	}
	
	public function getFilename()
	{
		if(!$this->getHasFile()) return false;
		
		return basename($_FILES[$this->getClientId()]['name']);
	}
	
	public function getFilesize()
	{
		if(!$this->getHasFile()) return false;
		
		return $_FILES[$this->getClientId()]['size'];
	}
	
	public function getType()
	{
		if(!$this->getHasFile()) return false;
		
		return $_FILES[$this->getClientId()]['type'];
	}
	
	public function getFileExtension()
	{
		if(!$this->getHasFile()) return false;
		
		$n = $this->getFilename();
		
		return substr($n, strrpos($n, '.'));
	}
	
	public function getTagName()
	{
		return 'input';
	}
	
	protected function onRender(TEventArgs $e)
	{
		if($form = $this->getPage()->FindControl('TForm'))
		{
			$form->setAttributeToRender('enctype','multipart/form-data');
		} 
		$this->setAttributeToRender('name', $this->getClientId());
		$this->setAttributeToRender('type', 'file');
	}
}