<?php
/**
 * 
 */
class TUiControl extends TControl
{
	protected static $_theme = 'wineo';
	protected $_config = array();
	
	protected function onCreate(TEventArgs $e)
	{
		TAssetManager::Publish('/Assets/jQuery/themes/'.self::$_theme.'/ui.theme.css');
		TAssetManager::Publish('/Assets/jQuery/jquery.js');
		TAssetManager::Publish('/Assets/jQuery/external/bgiframe/jquery.bgiframe.js');
		TAssetManager::Publish('/Assets/jQuery/ui/jquery-ui-core.js');
	}
	
	public function Config(array $config)
	{
		$this->_config = $config;
	}
	
	public function setConfigValue($name, $value)
	{
		$this->_config[$name] = $value;
	}
	
	public function Config2Json()
	{
		if(empty($this->_config)) return;
		
		return TVar::toJSON($this->_config);
	}
	
	public static function setTheme($theme)
	{
		self::$_theme = $theme;
	}
	
	protected function onRender(TEventArgs $e)
	{
	   if($form = $this->getPage()->FindControl('TForm'))
        {
            $form->AddScript("
                $.ui.dialog.defaults.bgiframe = true;
            ");
        }
	}
}