<?php
/**
 * 
 */
class TRangeValidator extends TValidator
{
	protected $_minValue = false;
	protected $_maxValue = false;

	public function getClientValidateFunction()
	{
		if($this->_minValue !== false && $this->_maxValue !== false)
		{
			return "var v = parseFloat(control.val()); return v >= {$this->_minValue} && v <= {$this->_maxValue};";
		}
		else if($this->_minValue === false && $this->_maxValue !== false)
		{
			return "var v = parseFloat(control.val()); return v <= {$this->_maxValue};";
		}
		else if($this->_minValue !== false && $this->_maxValue === false)
		{
			return "var v = parseFloat(control.val()); return v >= {$this->_minValue};";
		}
		else
		{
			return 'return true;';
		}
	}

	protected function onServerValidate(TValidatorEventArgs $e)
	{
		$ctl = $this->getControlToValidateObject();
		
		if($ctl instanceOf TDropDownList)
		{
			$v = TVar::toFloat($ctl->getSelectedValue());
		}
		else
		{
			$v = TVar::toFloat($ctl->getText());
		}

		if($this->_minValue !== false && $this->_maxValue !== false)
		{
			$this->setIsValid(($v >= $this->_minValue && $v <= $this->_maxValue));
		}
		else if($this->_minValue === false && $this->_maxValue !== false)
		{
			$this->setIsValid(($v <= $this->_maxValue));
		}
		else if($this->_minValue !== false && $this->_maxValue === false)
		{
			$this->setIsValid(($v >= $this->_minValue));
		}
		else
		{
			$this->setIsValid(true);
		}
	}

	public function setMinValue($v)
	{
		$this->_minValue = TVar::toInt($v);
	}

	public function getMinValue()
	{
		return $this->_minValue;
	}

	public function setMaxValue($v)
	{
		$this->_maxValue = TVar::toInt($v);
	}

	public function getMaxValue()
	{
		return $this->_maxValue;
	}
}
?>