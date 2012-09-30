<?php

class TActiveJoinResult extends TComponent {
	public $records = array();
	public $intersects = array();
	public $collection = array();
}

class TActiveJoinRecord extends TComponent {
	public $record = null;
	private $_set = null;

	public function __construct(TActiveRecord $record, TActiveJoinResult $set) {
		$this->record = $record;
		$this->_set = $set;
	}

	public function __get($p) {
		return $this->record->$p;
	}

	public function __set($p,$v) {
		$this->record->$p = $v;
	}

	public function get($table) {
		$ret = array();
		if($field = $this->_getIntersect($table)) {
			if(isset($this->_set->collection[$table])) foreach($this->_set->collection[$table] as $k => $v) {
				if($v->$field == $this->record->$field) {
					$ret[] = new TActiveJoinRecord($v,$this->_set);
				}
			}
		}
		return $ret;
	}

	private function _getIntersect($table) {
		$s = get_class($this->record);
		foreach($this->_set->intersects as $k => $v) {
			if(in_array($s,$v) && in_array($table,$v)) return $k;
		}
		return false;
	}
}

class TActiveJoin extends TComponent implements Countable {
	private $_join = array();
	private $_join_types = array();
	private $_result = null;
	private $_query = '';
	private $_main = null;
	private $_struct = array(); //tables structure [field] => table
	private $_struct2 = array(); //tables structure [table] => array of fields
	private $_intersects = array();

	//join types:
	const LEFT = 'LEFT';			//produces: ... left join `table` on ...
	const RIGHT = 'RIGHT';			//produces: ... right join `table` on ...
	const NATURAL_LEFT = 'NATURAL LEFT';	//produces: ... natural left join `table` ...
	const NATURAL_RIGHT = 'NATURAL JOIN';	//produces: ... natural right join `table` ...
	const CROSS = 'CROSS';			//produces: ... cross join `table` on ...
	const JOIN = '';			//produces: ... join `table` using on ...
	const NATURAL = 'NATURAL';		//produces: ... natural join `table` ...
	const INNER = 'INNER';			//produces: ... inner join `table` using on ...
	const OUTER = 'OUTER';			//produces: ... outer join `table` using on ...
	const LEFT_OUTER = 'LEFT OUTER';
	const RIGHT_OUTER = 'RIGHT OUTER';
	const LEFT_INNER = 'LEFT INNER';
	const RIGHT_RIGHT = 'RIGHT INNER';

	public function __construct(TActiveRecord $record) {
		$class = get_class($record);
		$this->_join[$class] = $record;
		$this->_main = $record;
		$this->_last = $record;

		foreach($record->_getStruct() as $k => $v) {
			$this->_struct[$k] = $class;
			$this->_struct2[$class][] = $k;
		}
	}

	/* join() adds table(s) to join, example usage (where table_name is an string or instance of ActiveRecord):
		join(new table_name);
		join('table_name');
		join(array('table_name','table_name_2' [, 'table_name_n']));
		join(array(new table_name, new table_name2, [, new table_name_n']));
		join(mix of array with ActiveRecords and tables names)

		the second parameter is a join type, one of the following:
			TActiveJoin::LEFT (DEFAULT),
			TActiveJoin::RIGHT,
			TActiveJoin::NATURAL_LEFT,
			TActiveJoin::NATURAL_RIGHT,
			TActiveJoin::CROSS,
			TActiveJoin::JOIN,
			TActiveJoin::NATURAL
			TActiveJoin::INNER,
			TActiveJoin::OUTER
	*/

	public function join($record,$type = self::LEFT) {
		if(is_object($record)) {
			if($record instanceOf TActiveRecord) {
				$class = get_class($record);
			} else throw new ActiveRecordException("Object of class '$class' must be an instance of TActiveRecord");
		} else if(is_string($record)) {
			if(class_exists($record)) {
				return $this->join(new $record, $type);
			} else throw new ActiveRecordException("Class '$record' not found");
		} else if(is_array($record)) {
			foreach($record as $v) {
				$this->join($v,$type);
			}
			return $this;
		}
		if(!isset($this->_join[$class])) {
			$this->_join[$class] = $record;
			$this->_join_types[$class] = $type;
			foreach($record->_getStruct() as $k => $v) {
				$this->_struct[$k] = $class;
				$this->_struct2[$class][] = $k;
			}
		} else throw new ActiveRecordException("Class '$class' already exists in join list");

		return $this;
	}

	private $_current;

	public function execute($additional_query = null) {
		$prev = $first = current(array_keys($this->_join));

		$query = 'SELECT ';
		$p = array();

		foreach($this->_struct2 as $k => $v) {
			foreach($v as $v2) {
				$p[] = "`$k`.`$v2` AS `{$k}.{$v2}`";
			}
		}

		$query .= implode(', ',$p).' FROM `'.$first.'` ';
		unset($p);
		$current = $this->_struct2[$first];

		$this->_result = new TActiveJoinResult;

		foreach($this->_join as $k => $v) {
			if($k == $first) continue;

			//if join is in correct order, we will find the common field here
			$intersect = array_intersect($current,$this->_struct2[$k]);

			//otherwise, we will look for the common field in all tables
			if(count($intersect) == 0) {
				foreach($this->_struct2 as $k2 => $v2) {
					$intersect = array_intersect($this->_struct2[$k],$v2);
					if(count($intersect) > 0) {
						$this->_result->intersects[current($intersect)][] = $k2;
						$this->_result->intersects[current($intersect)][] = $k;
						break;
					}
				}
			} else {
				$this->_result->intersects[current($intersect)][] = $k;
				$this->_result->intersects[current($intersect)][] = $prev;
				$k2 = false;
			}

			//finally, we should already have some common field but if not, we will
			//throw an exception because tables can't be joined

			if(count($intersect) > 0) {
				$query .= " {$this->_join_types[$k]} JOIN `$k`";
				if(!in_array($this->_join_types[$k],
					array(
						self::NATURAL_LEFT,
						self::NATURAL_RIGHT,
						self::NATURAL
					)
				)) {
					$f = current($intersect);
					//$query .= " using (`".current($intersect)."`)";
					$p = ($k2)?$k2:$prev;
					$query .= " ON `$p`.`$f` = `$k`.`$f`";
				}
			} else throw new ActiveRecordException("No common fields found in tables '$prev' and '$k'");

			$prev = $k;

			$current = $this->_struct2[$k];

		}

		$query .= " $additional_query";

		echo $query;

		$set = TActiveRecord::$_db->q($query);

		$collection = array();

		while($rec = $set->next()) {
			$arr = $rec->getFieldsArray();
			if(is_array($arr)) {
				foreach($arr as $key => $value) {

					$ex = explode('.',$key);
					$activeRecord = $ex[0];
					$field = $ex[1];

					$ar = $this->_join[$activeRecord];
					$pk = $ar->primaryKey;

					//Here we create a collection of ActiveRecords. Only one ActiveRecord is created for each
					//record with unique key

					if(!isset($collection[$activeRecord][$arr["$activeRecord.$pk"]]) && $arr["$activeRecord.$pk"] > 0) {
						$cr = $collection[$activeRecord][$arr["$activeRecord.$pk"]] = new $activeRecord;

						if($activeRecord == get_class($this->_main)) {
							$this->_result->records[] = new TActiveJoinRecord($cr,$this->_result);
						}
					}

					//Here we fill the ActiveRecord with returned values
					if(isset($collection[$activeRecord][$arr["$activeRecord.$pk"]])) {
						$collection[$activeRecord][$arr["$activeRecord.$pk"]]->$field = $value;
					}

				}
			}
		}
		$set->free();
		$this->_result->collection =& $collection;

		$ob = new ArrayObject($this->_result->records);
		return $ob->getIterator();
	}

	public function find(array $where = array()) {
		return $this->findAll($where,null, 1, 1, 1);
	}

	public function findAll(array $where = array(),$order = null, $page = 1, $limit = 0, $offset = 0) {
		$query = '';
		if(count($where) > 0) {
			$query = 'WHERE';
			foreach($where as $k => $v) {
				$query .= " `$k` = '".addslashes($v)."' AND";
			}
			$query = substr($query,0,-3);

			if($order != null) $query .= " ORDER BY $order";
			else $query .= " ORDER BY `".get_class($this->_main)."`.`{$this->_main->primaryKey}` ASC";

			if($limit > 0) {
				$page--;
				$query .= ' LIMIT '.($page*$offset).','.$limit;
			}

		}

		return $this->execute($query);
	}

	public function count() {
		return count($this->_result);
	}

}
?>