<?php
/**
 * 
 */
Engine::Using('System.Object');
Engine::Using('System.Web.Templating.TemplateTokenControl');
Engine::Using('System.Web.Templating.TemplateTokenProperty');

class TTemplateCompiler extends TObject
{
	const CONTROL_DECLARE_PREFIX 			= 'com';
	const PROPERTY_DECLARE_PREFIX 			= 'prop';
	
	const TOKENIZER_SPLIT 					= '(<[^>]*>)';
	const CONTROL_TAG_MATCH 				= '^<(/?[a-zA-Z:]+(\s+[a-zA-Z0-9]+="[^"]*")*\s*/?)>$';
	const PROPERTY_TAG_MATCH 				= '^<(/?[a-zA-Z:]+([a-zA-Z0-9]*))>$';
	const SELF_PROPERTIES_LIST_MATCH 		= '{^\s*<\?(.*)\?>\s*}s';
	const SELF_PROPERTIES_PROPERTY_MATCH 	= '{(?P<name>[a-zA-Z0-9]+)="(?P<value>[^"]*)"}';
	
	private $struct = array();
	private $current = null;
	private $property = null;
	private $ctl_num = 0;
	private $template_class;
	private $self_properties = array(); /*properties found within <?...?> tag*/
	private $open_properties = 0;
	private $prev_token = null;
	
	public function __construct(&$file, $class)
	{
		$this->template_class = $class;
		$this->Tokenize($file);
	}
	
	public function GetCode()
	{
		return $this->Struct2PHP();
	}
	
	public function Tokenize(&$source)
	{
		$source = trim($source);
		if(preg_match(self::SELF_PROPERTIES_LIST_MATCH, $source, $matches))
		{
			if(preg_match_all(self::SELF_PROPERTIES_PROPERTY_MATCH, $matches[1], $matches))
			{
				foreach($matches['name'] as $p => $v)
				{
					$this->self_properties[$v] = $matches['value'][$p];
				}
			}
			$source = preg_replace(self::SELF_PROPERTIES_LIST_MATCH, '', $source);
		}
		
		$split = preg_split('{'.self::TOKENIZER_SPLIT.'}', $source, -1, PREG_SPLIT_DELIM_CAPTURE);
		
		$stack = array();
		
		$this->prev_token = null;
		$this->open_properties = 0;
		$token = null;
		
		foreach($split as $element)
		{
			$this->prev_token = $token;
			if(preg_match('{^</?'.self::CONTROL_DECLARE_PREFIX.':[^>]*>$}', $element) && $this->open_properties == 0)
			{
				$token = $this->ProcessControlTag($element);
			}
			else if(preg_match('{^</?'.self::PROPERTY_DECLARE_PREFIX.':[^>]*>$}', $element))
			{
				$token = $this->ProcessPropertyTag($element);
			}
			else
			{
				$token = $this->ProcessLiteral($element);	
			}
			
			if($token === $this->prev_token) continue;
			
			if($token instanceOf TTemplateTokenProperty)
			{
				if($token->isOpening())
				{
					if($this->open_properties == 0)
					{
						$this->property = $token;	
					}
					else
					{
						$this->property->AppendValue($element);
					}
					$stack[] = $token;
					$this->open_properties++;
				}
				else
				{
					$this->current->AddProperty($this->property);
					$end = end($stack);
					
					if($token->getName() != $end->getName())
					{
						throw new CoreException('Unexpected </'.self::PROPERTY_DECLARE_PREFIX.':'.$token->getName().'>, expecting: </'.self::PROPERTY_DECLARE_PREFIX.':'.$end->getName().'>');
					}
					
					array_pop($stack);
					$this->open_properties--;
					
					if($this->open_properties == 0)
					{
						$this->property = null;
						continue;
					}
					else
					{
						$this->property->AppendValue($element);
					}
				}
			}
			else if($this->open_properties > 0)
			{
				$this->property->AppendValue($element);
			}
			else if($token instanceOf TTemplateTokenControl)
			{
					if($token->isOpening() && !$token->isSelfClosing())
					{
						if(!$this->current)
						{
							$this->struct[] = $token;
						}
						else
						{
							$this->current->AddChild($token);
						}
				
						$this->current = $token;
						$stack[] = $token;
					}
					else if($token->isSelfClosing())
					{
						if($this->current == null)
						{
							$this->struct[] = $token;
						}
						else
						{
							$this->current->AddChild($token);
						}
					}
					else if(!$token->isOpening())
					{
						if($token->getClass() != $this->current->getClass())
						{
							throw new CoreException('Unexpected </'.self::CONTROL_DECLARE_PREFIX.':'.$token->getClass().'>, expecting: </'.self::CONTROL_DECLARE_PREFIX.':'.$this->current->getClass().'>');
						}
						array_pop($stack);
						
						$this->current = end($stack); 
					}
				}
			
		}
		unset($source);
	}
	
	private function ProcessControlTag($element)
	{
		if(!preg_match('{'.self::CONTROL_TAG_MATCH.'}', $element))
		{
			throw new Exception('Control parse error');
		}
		
		$properties = array();
		
		if(preg_match_all('{\s+(?P<name>[a-zA-Z0-9]+)="(?P<value>[^"]*)"}', $element, $matches))
		{
			foreach($matches['name'] as $k => $v)
			{
				$properties[strtolower($v)] = $matches['value'][$k];
			}
		}
		
		if(!preg_match_all('{^</?'.self::CONTROL_DECLARE_PREFIX.':(?<name>[a-zA-Z0-9]+)}', $element, $matches))
		{
			throw new CoreException('Template parse error');
		}
		else
		{
			$class = $matches['name'][0];
		}
		
		$self_closing = preg_match('{/\s*>$}', $element);
		$only_closing = preg_match('{^</}', $element);
		
		
		return new TTemplateTokenControl($class, $properties, $self_closing, $only_closing);
	}
	
	private function ProcessPropertyTag($element)
	{
		if(!preg_match_all('{'.self::PROPERTY_TAG_MATCH.'}', $element, $matches))
		{
			throw new CoreException('Property parse error');
		}
		
		if(!preg_match_all('{^</?'.self::PROPERTY_DECLARE_PREFIX.':(?<name>[a-zA-Z0-9]+)}', $element, $matches))
		{
			throw new CoreException('Template parse error');
		}
		else
		{
			$class = $matches['name'][0];
		}
		
		return new TTemplateTokenProperty($class, '', !preg_match('{^</}', $element));
	}
	
	private function ProcessLiteral($literal)
	{
		if($this->open_properties == 0)
		{
			if(($this->prev_token instanceOf TTemplateTokenControl) && $this->prev_token->getClass() == 'Literal')
			{
				$props = $this->prev_token->getProperties();
				
				if(!isset($props['id']))
				{
					$this->prev_token->AppendProp('text', $literal);
				
					return $this->prev_token;
				}
			}
		}
		return new TTemplateTokenControl('Literal', array('text' => $literal), true, false);
	}
	
	
	private function Control2PHP($ctl, &$list_all = array(), &$instance = array(), &$create = array(), &$postback = array())
	{
		$class = $ctl->getClass();
		$prop = $ctl->getProperties();
		$childs = $ctl->getChilds();
		$parent = $ctl->getParent();
		
		if(!preg_match('{^[a-z_]+[a-z0-9_]*$}i', $prop['id'], $m))
        {
            throw new CoreException('Invalid value for ID attribute: '.$prop['id']);
        }
		
		
		if(is_object($parent))
		{
			$parent_props = $parent->getProperties();
			$prop['clientid'] = $parent_props['id'].'_'.$prop['id'];
			//$prop['systemid'] = $parent_props['systemid'].'$'.$prop['systemid'];
		}
		else
		{
			$prop['clientid'] = $prop['id'];
			//$prop['systemid'] = $prop['id'];
		}
		
		$selfName = '$this->'.$prop['id'];
		
		if(is_object($parent))
		{
			$parentName = '$this->'.$parent_props['id'];
		}
		
		$list_all[] = "\${$prop['id']}";
		$instance[] = $selfName.' = new T'.$class.';';
        //$instance[] = $selfName."->setSystemId('{$prop['systemid']}');";
		$instance[] = $selfName."->setClientId('{$prop['clientid']}');";
		$instance[] = $selfName."->setId('{$prop['id']}');";
		
		$create[] = $selfName."->RestoreViewState();";
		$create[] = $selfName."->RaiseEvent('onCreate');";
		$postback[] = "if($selfName instanceOf TTemplateControl) {$selfName}->getTemplate()->RaisePostBackEvents();";
		$postback[] = "else if(isset(\$_POST[{$selfName}->getViewState('name', '{$prop['clientid']}')])) ".$selfName."->RaisePostBackEvent();";
		
		foreach($prop as $pn => $pv)
		{
			if($pn == 'id' || $pn == 'clientid') continue;
			
			if(!is_numeric($pv) && $pv != 'true' && $pv != 'false')
			{
				$pv = str_replace('"','\"', $pv);
				$pv = str_replace('$','\$', $pv);
				$pv = str_replace(array("\n","\r","\t"), array('\n', '\r', '\t'), $pv);
				$pv = "\"$pv\"";
			}
			
			$instance[] = $selfName.'->'.$pn.' = '.$pv.';';
		}
		$instance[] = "\$this->AddControl($selfName);";
		
		if(is_object($parent))
		{
			$instance[] = $parentName.'->AddControl('.$selfName.');';
		}
		$instance[] = '';
		
		foreach($childs as $k => $child)
		{
			$child_props = $child->getProperties();
			if(!isset($child_props['id']) || $child_props['id'] == '')
			{
				$child->setProp('id',$this->template_class.'_ctl'.(++$this->ctl_num));
			}
			
			//$child->setProp('systemid',$this->template_class.'_ctl'.(++$this->ctl_num));
			$this->Control2PHP($child, $list_all, $instance, $create, $postback);
		}
	}
	
	private function MergeLiterals(&$struct)
	{

	}
	
	private function Struct2PHP()
	{
		$all = array();
		$instances = array();
		$create = array();
		$postback = array();
		
		$this->MergeLiterals($this->struct);
		
		foreach($this->struct as $k => $v)
		{
			if(!$v) continue;
			$child_props = $v->getProperties();
			if(!isset($child_props['id']) || $child_props['id'] == '')
			{
				$v->setProp('id', $this->template_class.'_ctl'.(++$this->ctl_num));
			}
			//$v->setProp('systemid',$this->template_class.'_ctl'.(++$this->ctl_num));
			$this->Control2PHP($v, $all, $instances, $create, $postback);
		}
		
		$date = date('Y-m-d H:i:s');
		
		if(!empty($all))
		{
			$instances = implode("\n\t\t", $instances);
			$properties_list = "protected\n\t\t".implode(",\n\t\t", $all)."\n\t;";
			$create = implode("\n\t\t", $create);
			$postback = implode("\n\t\t", $postback);
		}
		else
		{
			$instances = $properties_list = $create = $postback = null ;
		}
		
		$self_properties = '';
		
		foreach($this->self_properties as $name => $value)
		{
			$value = str_replace("'","\\'", $value);
			$self_properties .= '$this->_parentTemplateControl->set'.$name.'(\''.$value.'\');'."\n\t\t";
		}
		
		$ret = "<?php
/**
Template compiled on $date

WARNING: DO NOT EDIT THIS FILE. ANY HAND-MADE EDITION TO THIS
FILE MAY CAUSE UNEXPECTED BEHAVIOR IN YOUR APPLICATION.
THIS FILE IS ALSO AUTOMATICALLY REGENERATED IN DEBUG MODE
DURING EACH REQUEST TO '{$this->template_class}' CLASS.
*/

class {$this->template_class}_Template extends TControl
{
		
	$properties_list
	
	public function __construct(TTemplateControl \$parent)
	{
		\$this->_parentTemplateControl = \$parent;
		\$this->raiseEvent('onCreate');
	}
	
	protected function onCreate(TEventArgs \$args)
	{
		$instances
		
		$create
	}
	
	public function setInitialProperties()
	{
		$self_properties
	}
	
	public function getControl(\$id)
	{
		if(isset(\$this->_controls[\$id]))
		{
			return \$this->_controls[\$id];
		}
		else
		{
			throw new ControlException('Control '.get_class(\$this->_parentTemplateControl).'::$'.\$id.' does not exist');
		}
	}
	
	public function getTagName()
	{
		return null;
	}
	
	public function getHasEndTag()
	{
		return false;
	}
	
	public function Render()
	{
		foreach(\$this->getControls() as \$control)
		{
			if(\$control->getParent() === \$this) \$control->Render();
		}
	}
	
	public function RaisePostBackEvents()
	{
		$postback
	}
	
}
";
		return $ret;
	}
}