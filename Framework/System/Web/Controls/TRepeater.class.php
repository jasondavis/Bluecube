<?php
/**
 * 
 */
abstract class TRepeaterRenderer extends TTemplateControl
{
	public function __construct(TRepeater $repeater, $templateString)
	{	
		$parent_template_control = $repeater->getParentTemplateControl();
		$parent = $repeater->getParent();
		
		$parent_id = $parent->getId() == null ? $parent_template_control->getId() : $parent->getId();
		
		$this->setId($parent_id.'_'.$repeater->getId().'_'.get_class($this));
		$repeater->AddControl($this);

		//$cache_ident = md5($parent_id.'_'.$repeater->getId().'_'.get_class($this));
		$cache_ident = 'Repeaters:'.$parent_id.'_'.$repeater->getId().'_'.get_class($this);
		parent::__construct($templateString, $cache_ident);
	}
}

class TRepeaterEventArgs extends TEventArgs
{
	public $Renderer;
	public $Key;
	public $Data;
}

class TRepeaterItemRenderer extends TRepeaterRenderer {}
class TRepeaterAlternatingItemRenderer extends TRepeaterRenderer {}
class TRepeaterSeparatorRenderer extends TRepeaterRenderer {}
class TRepeaterEmptyRenderer extends TRepeaterRenderer {}
class TRepeaterHeaderRenderer extends TRepeaterRenderer {}
class TRepeaterFooterRenderer extends TRepeaterRenderer {}

class TRepeater extends TDataBoundControl
{
	protected $_itemTemplate;
	protected $_emptyTemplate;
	protected $_alternatingTemplate;
	protected $_separatorTemplate;
	protected $_headerTemplate;
	protected $_footerTemplate;
	
	protected $_items = array();
	protected static $_iteration = 0;
	
	protected function setItemTemplate($string)
	{
		$this->_itemTemplate = $string;
	}
	
	protected function setHeaderTemplate($string)
	{
		$this->_headerTemplate = $string;
	}
	
	protected function setFooterTemplate($string)
	{
		$this->_footerTemplate = $string;
	}
	
	protected function setSeparatorTemplate($string)
	{
		$this->_separatorTemplate = $string;
	}
	
	protected function setAlternatingTemplate($string)
	{
		$this->_alternatingTemplate = $string;
	}
	
	protected function setEmptyTemplate($string)
	{
		$this->_emptyTemplate = $string;
	}
	
	protected function _emptyBind()
	{
		if($this->_emptyTemplate)
		{
			$parent_id = $this->getParent()->getId() == null ? $this->getParent()->getParentTemplateControl()->getId() : $this->getParent()->getId();
		
			TViewstate::setPrefix($parent_id.self::$_iteration);
		
			$args = new TRepeaterEventArgs;
			$args->Renderer = new TRepeaterEmptyRenderer($this,  $this->_emptyTemplate);
		
			$this->RaiseEvent('onEmptyDataBind', $args);
		
			ob_start();
			$args->Renderer->Render();
			$this->_items[] = ob_get_clean();
		
			$this->RaiseEvent('onEmptyDataBound', $args);
		
			TViewstate::setPrefix('');
		
			self::$_iteration++;
		}
	}
	
	protected $_it = 0;
	protected $_count = -1;
		
	protected function _bind($item, $key)
	{
		if($this->_count == -1)
		{
			$this->_count = count($this->getDataSource());
		}
		
		$parent_id = $this->getParent()->getId() == null ? $this->getParent()->getParentTemplateControl()->getId() : $this->getParent()->getId();
		
		TViewstate::setPrefix($parent_id.self::$_iteration);
		
		$args = new TRepeaterEventArgs;
		
		if($this->_alternatingTemplate == null)
		{
			$args->Renderer = new TRepeaterItemRenderer($this, $this->_itemTemplate);
		}
		else
		{
			if($this->_it%2 == 0)
			{
				$args->Renderer = new TRepeaterItemRenderer($this, $this->_itemTemplate);
			}
			else
			{
				$args->Renderer = new TRepeaterAlternatingItemRenderer($this, $this->_alternatingTemplate);
			}
		}
		
		$args->Data = $item;
		$args->Key = $key;
		
		$this->RaiseEvent('onItemDataBind', $args);

		foreach($args->Renderer->getTemplate()->getControls() as $ctl)
		{
			if($ctl->getTagName() != null)
			{	
				$id = $ctl->getId();
								
				if(substr($id,0,2) != '__')
				{
					$ctl->AddCssClass($id);
				}
				$ctl->setClientId(null);
			}
		}
		
		ob_start();
		$args->Renderer->Render();
		$this->_items[] = ob_get_clean();
		
		$this->RaiseEvent('onItemDataBound', $args);
		
		TViewstate::setPrefix('');
		
		if($this->_separatorTemplate != null && $this->_it < $this->_count-1)
		{
			TViewstate::setPrefix($parent_id.self::$_iteration.'_separator');
			
			ob_start();
			$renderer = new TRepeaterSeparatorRenderer($this, $this->_separatorTemplate);
			
			$e = new TRepeaterEventArgs;
			$e->Renderer = $renderer;
			$this->RaiseEvent('onSeparatorRender', $e);
			
			$renderer->Render();
			$this->_items[] = ob_get_clean();
			
			TViewstate::setPrefix('');
		}
		
		$this->_it++;
		self::$_iteration++;
	}
	
	public function RenderContent()
	{
		foreach($this->_items as $item)
		{
			echo $item;
		}
		
		//unset($this->_items);
		$this->_items = array();
	}
	
	public function getTagName()
	{
		return null;
	}
	
	protected function onDataBind(TEventArgs $e)
	{
		if($this->_headerTemplate != null)
		{
			$renderer = new TRepeaterHeaderRenderer($this, $this->_headerTemplate);
			ob_start();
			
			$e = new TRepeaterEventArgs;
			$e->Renderer = $renderer;
			$this->RaiseEvent('onHeaderRender', $e);
			
			$renderer->Render();
			$this->_items[] = ob_get_clean();
		}
	}
	
	protected function onDataBound(TEventArgs $e)
	{
		if($this->_footerTemplate != null)
		{
			$renderer = new TRepeaterFooterRenderer($this, $this->_footerTemplate);
			ob_start();
			
			$e = new TRepeaterEventArgs;
			$e->Renderer = $renderer;
			$this->RaiseEvent('onFooterRender', $e);
			
			$renderer->Render();
			$this->_items[] = ob_get_clean();
		}
	}
	
	protected function onHeaderRender(TRepeaterEventArgs $e) {}
	protected function onFooterRender(TRepeaterEventArgs $e) {}
	protected function onSeparatorRender(TRepeaterEventArgs $e) {}
}
