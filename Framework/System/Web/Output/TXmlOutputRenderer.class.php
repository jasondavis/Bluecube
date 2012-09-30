<?php
class TXmlOutputRenderer implements IOutputRenderer
{
    public function render($data)
    {
        return $this->_render($data);
    }
    
    private function _render($data, $level = 0, $header = true)
    {
        if($header)
        {
            $ret = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        }
        else
        {
            $ret = str_repeat("\t", $level);
        }
        
        if(is_array($data) || is_object($data))
        {
            if(is_array($data))
            {
                $ret .= "<array>\n";
            }
            else
            {
                $ret .= '<object class="'.get_class($data).'">'."\n";
            }
            
            foreach($data as $key => $val)
            {
                $type = strtolower(gettype($val));
                
                $key = htmlspecialchars($key);
                $ret .= str_repeat("\t", $level+1);
                $ret .= "<item key=\"$key\" type=\"$type\">\n";
                $ret .= $this->_render($val, $level+2, false);
                $ret .= str_repeat("\t", $level+1);
                $ret .= "</item>\n";
            }
            
            $ret .= str_repeat("\t", $level);
            
            if(is_array($data))
            {
                $ret .= "</array>\n";
            }
            else
            {
                $ret .= '</object>';
            }
        }
        else
        {
            if(!is_numeric($data) && !is_bool($data) && !is_null($data))
            {
               $c = '<![CDATA['.$data.']]>';
            }
            else if(is_bool($data))
            {
                $c = $data ? 'true' : 'false';
            }
            else if(is_null($data))
            {
                $c = '';                
            }
            else
            {
                $c = $data;
            }
            
            $type = strtolower(gettype($data));
            
            $ret .= '<'.$type.'>'.$c.'</'.$type.'>'."\n";
        }
        
        return $ret;
    }
}