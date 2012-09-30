<?php
class TViewstateHttpController extends TObject implements IViewstateController
{
	protected $_identifier;
	
	public function __construct(array $options = array(), $identifier = null)
	{
		$this->_identifier = $identifier;
	}
	
	public function Read()
	{
		$serialized = gzuncompress(base64_decode($this->_identifier));
		return TObject::Unserialize($serialized);
	}
	
	public function Write($data)
	{
		$serialized = gzcompress(TObject::Serialize($data), 9);
		$serialized = base64_encode($serialized);
		return str_replace('=', '', $serialized);
	}
}