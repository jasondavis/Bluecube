<?php
/**
 * 
 */
class TRichEdit extends TControl
{
	public function getTagName()
	{
		return 'textarea';
	}
	
	public function getAllowChildControls()
	{
		return false;
	}
	
	public function getHasEndTag()
	{
		return true;
	}
	
	protected function onCreate(TEventArgs $e)
	{
		parent::onCreate($e);
		
		TAssetManager::Publish('/Assets/tiny_mce/jscripts/tiny_mce/tiny_mce.js');
		
		if(!empty($_POST))
		{
			$name = $this->getClientId();
			
			$this->setText(isset($_POST[$name]) ? $_POST[$name] : null);
		}
	}
	
	public function setText($text)
	{
		$this->setViewState('text', $text);
	}
	
	public function getText()
	{
		$text = $this->getViewState('text');
		
		$text = preg_replace('{<style[^>]*>.*</style>}sU',		'',		$text);
		$text = preg_replace('{<!--.*-->}sU',					'',		$text);
		$text = preg_replace('{<meta[^>]*>}',					'',		$text);
		$text = preg_replace('{<head[^>]*>.*</head>}sU',		'',		$text);
		$text = preg_replace('{<body[^>]*>}',					'',		$text);
		$text = preg_replace('{</body>}',						'',		$text);
		$text = preg_replace('{<link[^>]*>}',					'',		$text);
		$text = preg_replace('{<html[^>]*>}',					'',		$text);
		$text = preg_replace('{</html>}',						'',		$text);
		$text = preg_replace('{<title[^>]*>.*</title>}sU',		'',		$text);
		
		return $text;
	}
	
	public function RenderContent()
	{
		echo htmlspecialchars($this->getText());
	}
	
	protected function onRender(TEventArgs $e)
	{
		$this->setAttributeToRender('name', $this->getClientId());
		
		if($form = $this->getPage()->FindControl('TForm'))
		{
			$form->AddRawScript("
				tinyMCE.init({
					mode: 'exact',
					elements: '".$this->getClientId()."',
					theme: 'advanced',
					//plugins: 'table,advimage,advlink,inlinepopups,media,searchreplace,contextmenu,paste',
					plugins: 'inlinepopups',
					//theme_advanced_buttons1: 'bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,removeformat,|,forecolor,backcolor,|,link,unlink,|,search,replace,|,bullist,numlist',
					theme_advanced_buttons1: 'bold,italic,underline,|,image,link',
					//theme_advanced_buttons2: 'cut,copy,paste,|,undo,redo,pastetext,pasteword,|,tablecontrols,|,image',
					theme_advanced_buttons2: '', 
					theme_advanced_buttons3: '',
					theme_advanced_buttons4: '',
					skin: 'o2k7',
					skin_variant: 'silver',
					theme_advanced_toolbar_location: 'top',
					content_css: '/Styles/TinyLayout.css',
					cleanup: true,
					cleanup_on_startup : true,
					convert_fonts_to_spans: true,
					force_p_newlines: true,
					entity_encoding: 'raw',
					fix_list_elements: true,
					fix_table_elements: true,
					fix_nesting: true,
					force_br_newlines: true,
        			forced_root_block: '',
        			language: 'pl',
        			dialog_type: 'modal',
        			valid_elements: 'a[href],strong,b,i,u,br,hr,p[align],em,span,img[src|alt|width|height|align|style|hspace|vspace],ol,ul,li'
				});
			");
		}
	}
}