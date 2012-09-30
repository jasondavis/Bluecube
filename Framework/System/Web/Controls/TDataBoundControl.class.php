<?php
/**
 * 
 */
abstract class TDataBoundControl extends TControl
{
	protected $_dataSource = array();
	protected $_isDataBound = false;
	
	protected function onRestoreStateComplete(TEventArgs $args)
	{
		if($this->isPostBack())
		{
			$this->_dataSource = $this->getDataSource();
			if(!empty($this->_dataSource)) $this->DataBind();
		}
	}
	
	public function setDataSource($dataSource)
	{
		$this->setViewState('dataSource', $dataSource);
	}
	
	public function getDataSource()
	{
		return $this->getViewState('dataSource', array());;
	}
	
	public function DataBind()
	{
		if($this->_isDataBound) return;
		
		$this->_isDataBound = true;
		
		$ds = $this->getDataSource();
		
		if(empty($ds) || count($ds) == 0)
		{
			$this->RaiseEvent('onEmptyDataBind');
			
			$this->_emptyBind();
			
			$this->RaiseEvent('onEmptyDataBound');
		}
		else
		{
			$this->RaiseEvent('onDataBind');
		
			foreach($ds as $key => $item)
			{
				$this->_bind($item, $key);
			}
			
			$this->RaiseEvent('onDataBound');
		}
	}
	
	protected function _emptyBind()
	{
		$this->RaiseEvent('onEmptyDataBind');
		$this->RaiseEvent('onEmptyDataBound');
	}
	
	protected function _bind($item, $key)
	{
		$args = new TEventArgs;
		$args->Key = $key;
		$args->Item = $item;
		
		$this->raiseEvent('onItemDataBind', $args);
		$this->raiseEvent('onItemDataBound', $args);
	}
	
	protected function onDataBind(TEventArgs $e){}
	protected function onItemDataBind(TEventArgs $e){}
	protected function onItemDataBound(TEventArgs $e){}
	protected function onDataBound(TEventArgs $e){}
	protected function onEmptyDataBind(TEventArgs $e){}
	protected function onEmptyDataBound(TEventArgs $e){}
}