<?php
/**
 * 
 */
class THead extends TControl
{
	private $dom;
	private $xp;
	
	public function getTagName()
	{
		return 'head';
	}
	
	public function getAllowChildControls()
	{
		return array('TLiteral');
	}
	
	public function getEnableViewState()
	{
		return false;
	}
	
	private function _createDom()
	{
		if($this->dom) return;
		
		$this->dom = new DOMDocument;
		$this->dom->LoadXML('<head>'.$this->getText().'</head>');
		$this->xp = new DOMXPath($this->dom);
	}
	
	public function setTitle($title)
	{
		$this->_createDom();
		
		$nodes = $this->dom->getElementsByTagName('title');
		
		if($nodes->length > 0)
		{
			$nodes->item(0)->nodeValue = htmlspecialchars($title);
		}
	}
	
	public function getTitle()
	{
		$this->_createDom();
		
		$nodes = $this->dom->getElementsByTagName('title');
		
		if($nodes->length > 0)
		{
			return html_entity_decode($nodes->item(0)->nodeValue);
		}
	}
	
	public function setMeta($name, $content)
	{
		$this->_createDom();
		
		$nodes = $this->xp->Query('//head/meta[@name="'.$name.'"]');
		
		if($nodes->length > 0)
		{
			$nodes->item(0)->setAttribute('content', $content);
		}
		else
		{
			$this->dom->documentElement->appendChild($this->dom->createTextNode("\t"));
			$node = $this->dom->createElement('meta');
			$node->setAttribute('name', $name);
			$node->setAttribute('content', htmlspecialchars($content));
			$this->dom->documentElement->appendChild($node);
			$this->dom->documentElement->appendChild($this->dom->createTextNode("\n"));
		}
	}
	
	public function getMeta($name)
	{
		$this->_createDom();
		
		$nodes = $this->xp->Query('//head/meta[@name="'.$name.'"]');
		
		if($nodes->length > 0)
		{
			return html_entity_decode($nodes->item(0)->getAttribute('content'));
		}
	}
	
	public function setMetaHttpEquiv($name, $content)
	{
		$this->_createDom();
		
		$nodes = $this->xp->Query('//head/meta[@http-equiv="'.$name.'"]');
		
		if($nodes->length > 0)
		{
			$nodes->item(0)->setAttribute('content', $content);
		}
		else
		{
			$this->dom->documentElement->appendChild($this->dom->createTextNode("\t"));
			$node = $this->dom->createElement('meta');
			$node->setAttribute('http-equiv', $name);
			$node->setAttribute('content', $content);
			$this->dom->documentElement->appendChild($node);
			$this->dom->documentElement->appendChild($this->dom->createTextNode("\n"));
		}
	}
	
	public function LinkCss($css)
	{
		$this->_createDom();
		
		$this->Link($css, 'stylesheet', 'text/css');
	}
	
	public function LinkScript($href, $type = 'text/javascript')
	{
		$this->_createDom();
		
		$nodes = $this->xp->Query('//head/script[@src="'.$href.'"]');
		
		if($nodes->length > 0)
		{
			$nodes->item(0)->setAttribute('type', $type);
		}
		else
		{
			$this->dom->documentElement->appendChild($this->dom->createTextNode("\t"));
			$node = $this->dom->createElement('script');
			$node->setAttribute('type', $type);
			$node->setAttribute('src', $href);
			$this->dom->documentElement->appendChild($node);
			$this->dom->documentElement->appendChild($this->dom->createTextNode("\n"));
		}
	}
	
	public function Link($href, $rel, $type, $title = '')
	{
		$this->_createDom();
		
		$nodes = $this->xp->Query('//head/link[@href="'.$href.'"]');
		
		if($nodes->length > 0)
		{
			if($title) $nodes->item(0)->setAttribute('title', $title);
			$nodes->item(0)->setAttribute('rel', $rel);
			$nodes->item(0)->setAttribute('type', $type);
		}
		else
		{
			$this->dom->documentElement->appendChild($this->dom->createTextNode("\t"));
			$node = $this->dom->createElement('link');
			if($title) $node->setAttribute('title', $title);
			$node->setAttribute('rel', $rel);
			$node->setAttribute('href', $href);
			$node->setAttribute('type', $type);
			$this->dom->documentElement->appendChild($node);
			$this->dom->documentElement->appendChild($this->dom->createTextNode("\n"));
		}
	}
	
	public function Render()
	{
		$assets = TAssetManager::getPublished();
		if(!empty($assets))
		{
			foreach($assets as $asset)
			{
				$pos = strrpos($asset,'.');
				$ext = strtolower(substr($asset,$pos+1));
				
				switch($ext)
				{
					case 'css': $this->LinkCss($asset); break;
					case 'js': $this->LinkScript($asset); break;
				}
			}
		}
		
		$this->SetMeta('X-Powered-By', Engine::IDENTIFIER.' '.Engine::VERSION);
		
		if($this->dom)
		{
			$code = html_entity_decode($this->dom->saveXML(), ENT_COMPAT, 'utf-8');
			$code = preg_replace('{<\?.*\?>}', '', $code);
			$code = preg_replace('{(<script)(.*)(/>)}','\\1\\2></script>', $code);
			
			echo $code;
		}
		else
		{
			parent::Render();
		}
	}
	
	public function RenderAttributes() {} //do nothing while rendering attributes
}