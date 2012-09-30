<?php
/**
 * 
 */
class TCalendar extends TControl
{
	protected $_dayVar = 'cday';
	protected $_monthVar = 'cmonth';
	protected $_yearVar = 'cyear';
	protected $_allowDateChange = true;
	
	public function getTagName()
	{
		return 'div';
	}
	
	public function getAllowChildControls()
	{
		return array('TCalendarWeek');
	}
	
	public function setAllowDateChange($allow)
	{
		$this->_allowDateChange = TVar::toBool($allow);
	}
	
	public function getAllowDateChange()
	{
		return $this->_allowDateChange;
	}
	
	public function setMonthVar($name)
	{
		$this->_monthVar = $name;
	}
	
	public function setYearVar($name)
	{
		$this->_yearVar = $name;
	}
	
	public function setDayVar($name)
	{
		$this->_dayVar = $name;
	}
	
	public function getMonthVar()
	{
		return $this->_monthVar;
	}
	
	public function getYearVar()
	{
		return $this->_yearVar;
	}
	
	public function getDayVar()
	{
		return $this->_dayVar;
	}
	
	public function setDay($day)
	{
		$this->setViewState('Day', $day);
	}
	
	public function setMonth($month)
	{
		if($month < 1 || $month > 12) $month = (int) date('m');
		$this->setViewState('Month', $month);
	}
	
	public function setYear($year)
	{
		if($year < 1970 || $year > 9999) $year = (int) date('Y');
		$this->setViewState('Year', $year);
	}
	
	public function getDay()
	{
		return $this->getViewState('Day', (int) date('d'));
	}
	
	public function getMonth()
	{
		return $this->getViewState('Month', (int) date('m'));
	}
	
	public function getYear()
	{
		return $this->getViewState('Year', (int) date('Y'));
	}
	
	public function setDayNames(array $dayNames)
	{
		$this->setViewState('DayNames', $dayNames);
	}
	
	public function getDayNames()
	{
		return $this->getViewState('DayNames', array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'));
	}
	
	public function setMonthNames(array $monthNames)
	{
		$this->setViewState('MonthNames', $monthNames);
	}
	
	public function getMonthNames()
	{
		return $this->getViewState('MonthNames', array('January','February','March','April','May','June','July','August','September','October','November','December'));
	}
	
	protected function onCreate(TEventArgs $e)
	{
		TAssetManager::Publish('/Assets/Styles/TCalendar.css');
	}
	
	protected function onRender(TEventArgs $e)
	{
		if($this->getAllowDateChange() && ($day = THttpRequest::Get($this->_dayVar))) $this->setDay($day);
		if($this->getAllowDateChange() && ($month = THttpRequest::Get($this->_monthVar))) $this->setMonth($month);
		if($this->getAllowDateChange() && ($year = THttpRequest::Get($this->_yearVar))) $this->setYear($year);
			
		if($this->getMonth() == 1)
		{
			$prev_month = 12;
			$prev_month_year = $this->getYear()-1;
		}
		else
		{
			$prev_month = $this->getMonth()-1;
			$prev_month_year = $this->getYear();
		}
		
		if($this->getMonth() == 12)
		{
			$next_month = 1;
			$next_month_year = $this->getYear()+1;
		}
		else
		{
			$next_month = $this->getMonth()+1;
			$next_month_year = $this->getYear();
		}		
		
		$time = mktime(0,0,0,$this->getMonth(), $this->getDay(), $this->getYear());
		$first_day_time = mktime(0,0,0,$this->getMonth(), 1, $this->getYear());
		$next_month_time = mktime(0,0,0,$next_month,$this->getDay(),$next_month_year);
		$prev_month_time = mktime(0,0,0,$prev_month,$this->getDay(),$prev_month_year);
		
		$first_day_in_month = date('N', $first_day_time)-1;
		
		$months = $this->getMonthNames();
		
		$days_in_month = date('t', $time);
		$day_in_week = date('N', $time)-1;
		$days_in_prev_month = date('t',$prev_month_time);
		$days_in_next_month = date('t',$next_month_time);
		
		$weeks = 6;
		
		$day = 1;
		$is_next_month = false;
		
		for($row = 0; $row < $weeks; $row++)
		{
			$week = new TCalendarWeek(date('W', $time));
			
			$this->AddControl($week);
			$week->RaiseEvent('onCreate');
			
			for($col = 0; $col < 7; $col++)
			{
				//Current month
				if(!$is_next_month && (($row == 0 && $col >= $first_day_in_month) || ($row > 0 && $day <= $days_in_month)))
				{
					$render = array(
						'css' => array('day', 'current-month-day'),
						'day' => $day++,
						'month' => $this->getMonth(),
						'year' => $this->getYear()
					);
				}
				//Previous month
				else if(!$is_next_month && $row == 0 && $col < $first_day_in_month)
				{
					$render = array(
						'css' => array('day', 'prev-month-day'),
						'day' => $days_in_prev_month-$first_day_in_month+$col+1,
						'month' => $prev_month,
						'year' => $prev_month_year
					);
				}
				//Next month
				else
				{
					if(!$is_next_month && $day > 7)
					{
						$is_next_month = true;
						$day = 1;
					}
					$render = array(
						'css' => array('day', 'next-month-day'),
						'day' => $day++,
						'month' => $next_month,
						'year' => $next_month_year
					);
				}
				
				$render['dayOfWeek'] = $col+1;
				
				if($col == 5) $render['css'][] = 'weekend weekend-saturday';
				if($col == 6) $render['css'][] = 'weekend weekend-sunday';
				
				if((int) $render['day'] == (int) date('d') && $render['month'] == (int) date('m') && $render['year'] == (int) date('Y'))
				{
					$render['css'][] = 'current-day';	
				}
				else if((int) $render['day'] == (int) date('d'))
				{
					$render['css'][] = 'same-day';
				}
				
				$dayCtl = new TCalendarDay($render['day'], $render['month'], $render['year'], $col+1, date('z', $time));
				$week->AddControl($dayCtl);
				$week->RaiseEvent('onCreate');
				
				$dayCtl->Text = $render['day'];
				$dayCtl->setCssClass(implode(' ', $render['css']));
			}
		}
	}
	
	public function RenderContent()
	{
		$days = $this->getDayNames();
		
		echo '<table class="TCalendar">';
		
		echo '<thead><tr>';
		
		$monthNames = $this->getMonthNames();
		$monthName = $monthNames[$this->getMonth()-1];
		
		if($this->getMonth() == 1)
		{
			$prev_month = 12;
			$prev_month_year = $this->getYear()-1;
		}
		else
		{
			$prev_month = $this->getMonth()-1;
			$prev_month_year = $this->getYear();
		}
		
		if($this->getMonth() == 12)
		{
			$next_month = 1;
			$next_month_year = $this->getYear()+1;
		}
		else
		{
			$next_month = $this->getMonth()+1;
			$next_month_year = $this->getYear();
		}
		
		if($this->getAllowDateChange())
		{
			$back_year_link = array();
			$back_year_link[$this->getMonthVar()] = $this->getMonth();
			$back_year_link[$this->getYearVar()] = $this->getYear()-1;
			
			$back_month_link = array();
			$back_month_link[$this->getMonthVar()] = $prev_month;
			$back_month_link[$this->getYearVar()] = $prev_month_year;
			
			$next_year_link = array();
			$next_year_link[$this->getMonthVar()] = $this->getMonth();
			$next_year_link[$this->getYearVar()] = $this->getYear()+1;
			
			$next_month_link = array();
			$next_month_link[$this->getMonthVar()] = $next_month;
			$next_month_link[$this->getYearVar()] = $next_month_year;
			
			
			
			echo '<td style="text-align:left">';
			echo '<a href="?'.http_build_query($back_year_link).'" class="back-year-link"></a>';
			echo '<a href="?'.http_build_query($back_month_link).'" class="back-month-link"></a>';
			echo '</td>';
			
			echo '<td colspan="5" style="text-align:center">'.$monthName.' '.$this->getYear().'</td>';
			
			echo '<td style="text-align:right">';
			echo '<a href="?'.http_build_query($next_year_link).'" class="next-year-link"></a>';
			echo '<a href="?'.http_build_query($next_month_link).'" class="next-month-link"></a>';
			echo '</td>';
			
			echo '</tr><tr>';
		}
		else
		{
			echo '<td colspan="7" style="text-align:center">'.$monthName.' '.$this->getYear().'</td>';
			echo '</tr><tr>';
		}
		
		for($i = 0; $i < 7; $i++)
		{
			echo '<td style="width:14%">'.$days[$i].'</td>';
		}
		echo '</tr></thead>';
		echo "<tbody>\n";
		
		parent::RenderContent();
		
		echo "</tbody>\n";
		echo "</table>";
	}
	
	protected function onWeekRender(TCalendarWeekEventArgs $e) {}
	protected function onDayRender(TCalendarDayEventArgs $e) {}
}

class TCalendarWeek extends TControl
{
	protected $_weekOfYear;
	
	public function __construct($weekOfYear)
	{
		$this->_weekOfYear = $weekOfYear;
	}
	
	public function getTagName()
	{
		return 'tr';
	}
	
	public function getAllowChildControls()
	{
		return array('TCalendarDay');
	}
	
	protected function onRender(TEventArgs $e)
	{
		$args = new TCalendarWeekEventArgs;
		$args->Renderer = $this;
		$args->WeekOfYear = $this->_weekOfYear;
		$this->setClientId(null);
		$this->getParent()->RaiseEvent('onWeekRender', $args);
	}
}

class TCalendarDay extends TControl
{
	protected $_day;
	protected $_month;
	protected $_year;
	protected $_dayOfWeek;
	protected $_dayOfYear;
	
	public function __construct($day, $month, $year, $dayOfWeek, $dayOfYear)
	{
		$this->_day = $day;
		$this->_month = $month;
		$this->_year = $year;
		$this->_dayOfWeek = $dayOfWeek;
		$this->_dayOfYear = $dayOfYear;
	}
	
	public function getTagName()
	{
		return 'td';
	}
	
	protected function onRender(TEventArgs $e)
	{
		$args = new TCalendarDayEventArgs;
		$args->Renderer = $this;
		$args->Day = $this->_day;
		$args->Month = $this->_month;
		$args->Year = $this->_year;
		$args->DayOfWeek = $this->_dayOfWeek;
		$args->DayOfYear = $this->_dayOfYear;
		$this->setClientId(null);
		
		$this->getParent()->getParent()->RaiseEvent('onDayRender', $args);
	}
}

class TCalendarDayEventArgs extends TEventArgs
{
	public $Renderer;
	public $Day;
	public $Month;
	public $Year;
	public $DayOfWeek;
	public $DayOfYear;
}

class TCalendarWeekEventArgs extends TEventArgs
{
	public $Renderer;
	public $WeekOfYear;
}