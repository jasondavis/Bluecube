<?php
class ActiveRecordException extends Exception {
	public function __construct($msg) {
		$this->message = $msg;
	}
}

class TActiveRecordCriteria extends TObject {
	public $orderBy = array();
	public $condition = null;
	public $parameters = array();
	public $limit = 0;
	public $offset = 0;

	public function __toString() {
		$ret = $this->condition;
		foreach($this->parameters as $k => $v) {
			$ret = str_replace(":$k","'".addslashes($v)."'",$ret);
		}

		if(count($this->orderBy) > 0) {
			$ret .= ' order by ';
			foreach($this->orderBy as $k => $v) {
				$ret .= "`$k` $v,";
			}
			$ret = substr($ret,0,-1);
		}
		
		if($this->limit > 0) $ret .= " limit {$this->offset},{$this->limit}";
		return $ret;
	}
}

class TActiveRecord extends TComponent {
	public $belongsTo = array();
	public $hasMany = array();
	public $hasOne = array();
	public $parentAsArray = true;
	public $cache = null;
	public $cacheObjects = null;
	public $primaryKey = 'id';
	public $mapIdToPk = true;

	public static $_db = null;
	protected static $_struct = array();
	private static $_objectCache = array();

	private $_record = array();
	private $_childs = array();
	private $_childsOne = array();
	private $_changed = false;
	private $_table = null;
	private $_changedFields = array();
	
	final public function __construct($cache = false, $cacheObjects = false) {
		if(self::$_db == null) self::$_db = new TMySQL(new TDatabaseConnection);
		$this->setTable(get_class($this));
		if($this->cache == null) $this->cache = $cache;
		if($this->cacheObjects == null) $this->cacheObjects = $cacheObjects;
	}

	final public function setTable($table) {
		$this->_table = $table;

		if(!isset(self::$_struct[$this->_table])) self::$_struct[$this->_table] = self::$_db->describe($this->_table);

		if(isset(self::$_db->primaryKeys[$table])) $this->primaryKey = self::$_db->primaryKeys[$table];

		if(!isset(self::$_struct[$this->_table][$this->primaryKey])) throw new ActiveRecordException("The primary key '{$this->primaryKey}' of table '{$this->_table}' not found");
	}

	final public function getRecord() {
		return $this->_record;
	}

	final public function _getStruct() {
		return self::$_struct[$this->_table];
	}

	final public function getParent($parentName, array $search = array()) {
		if(in_array($parentName,$this->belongsTo)) {
			if(class_exists($parentName)) {
				$ob = new $parentName;

				if($parentName == $this->_table) {
					$pk = $this->primaryKey;
					$fk = $this->primaryKey.'_';
				} else {
					$pk = $fk = $ob->primaryKey;
				}

				$fnd = $ob->findAll(array_merge($search,array($pk => $this->_record[$fk])));
				if($this->parentAsArray) return $fnd;
				else if(isset($fnd[0])) {
					return $fnd[0];
				} else return null;
				
			} else throw new ActiveRecordException("No class '$parentName' found");
		} else throw new ActiveRecordException("'".get_class($this)."' does not belong to '$parentName'");
	}

	final public function getChild($table,array $search = array(),$order = null,$page = 1,$limit = 0,$offset = 0) {

		if(in_array($table,$this->hasMany)) {
			if(isset($this->_record[$this->primaryKey]) && $this->_record[$this->primaryKey] != null) {
				if(class_exists($table)) {

					$ob = new $table;

					if($table == $this->_table) {
						$pk = $this->primaryKey;
						$fk = $this->primaryKey.'_';
					} else {
						$pk = $fk = $this->primaryKey;
					}

					return $ob->findAll(array_merge(array($fk => $this->_record[$pk]),$search),$order,$page,$limit,$offset);

				} else throw new ActiveRecordException("No class '$table' found");
			} else throw new ActiveRecordException("'{$this->_table}' has no record");
		} else if(isset($this->hasMany[$table])) {
			$A = $this->_table; //tags
			$B = $this->hasMany[$table]; //tags_files
			$C = $table; //files

			$b = new $this->hasMany[$table];
			$c = new $table;
			$apk = $this->primaryKey;
			$bpk = $b->primaryKey;
			$cpk = $c->primaryKey;

			$sql = "select C.* from `$A` A left join `$B` B on (B.$apk = A.$apk) left join `$C` C on (C.$cpk = B.$cpk) where A.$apk = '{$this->{$this->primaryKey}}'";
			foreach($search as $k => $v) {
				if(strtolower($k) == 'id' && $this->mapIdToPk || strtolower($k) == 'pk') $k = $this->primaryKey;
				$sql .= " and `$k` = '".addslashes($v)."'";
			}
			if($order != null) $sql .= " order by $order";

			if($limit > 0) {
				$page--;
				$sql = ' limit '.($page*$offset).', '.$limit;
			}
			
			//$ob = new $table;
			return $c->findBySql($sql);

		} else if(in_array($table,$this->hasOne)) {

			if(isset($this->_record[$this->primaryKey]) && $this->_record[$this->primaryKey] != null) {
				if(class_exists($table)) {

					$ob = new $table;
					$r = $ob->find(array_merge(array($this->primaryKey => $this->_record[$this->primaryKey]),$search));
					if(count($r) == 1) return $r[0]; else return null;

				} else throw new ActiveRecordException("No class '$table' found");
			} else throw new ActiveRecordException("'{$this->_table}' has no record");

		} else throw new ActiveRecordException("'$table' does not belong to childs of '{$this->_table}'");
	}

	final public function getRelated($args = null) {
		if(!is_array($args)) $args = func_get_args();
		$hasMany = array();
		$hasOne = array();
		$belongsTo = array();

		if(count($args) == 0) {
			$hasMany = $this->hasMany;
			$hasOne = $this->hasOne;
			$belongsTo = $this->belongsTo;
		} else {
			foreach($args as $v) {
				if(in_array($v,$this->hasMany)) $hasMany[] = $v;
				else if(isset($this->hasMany[$v])) $hasMany[$v] = $this->hasMany[$v];
				else if(in_array($v,$this->hasOne)) $hasOne[] = $v;
				else if(in_array($v,$this->belongsTo)) $belongsTo[] = $v;
			}
		}

		/* TODO */
	}

	final public function getChildCount($table,array $search = array()) {
		if(in_array($table,$this->hasMany)) {
			if(isset($this->_record[$this->primaryKey]) && $this->_record[$this->primaryKey] != null) {
				if(class_exists($table)) {
					$ob = new $table;

					if($table == $this->_table) {
						$pk = $this->primaryKey;
						$fk = $this->primaryKey.'_';
					} else {
						$pk = $fk = $this->primaryKey;
					}

					$c = $ob->findAllCount(array_merge(array($pk => $this->_record[$fk]),$search));
					return (isset($c[0]))?$c[0]->cnt:0;
				} else throw new ActiveRecordException("No class '$table' found");
			} else throw new ActiveRecordException("'{$this->_table}' has no record");
		} else if(isset($this->hasMany[$table])) {
			$A = $this->_table; //tags
			$B = $this->hasMany[$table]; //tags_files
			$C = $table; //files

			$b = new $this->hasMany[$table];
			$c = new $table;
			$apk = $this->primaryKey;
			$bpk = $b->primaryKey;
			$cpk = $c->primaryKey;

			$sql = "select C.* from `$A` A left join `$B` B on (B.$apk = A.$apk) left join `$C` C on (C.$cpk = B.$cpk) where A.$apk = '{$this->{$this->primaryKey}}'";

			foreach($search as $k => $v) {
				if(strtolower($k) == 'id' && $this->mapIdToPk || strtolower($k) == 'pk') $k = $this->primaryKey;
				$sql .= " and `$k` = '".addslashes($v)."'";
			}

			$cnt = $c->findBySql($sql);
			return isset($cnt[0])?$cnt[0]->cnt:0;

		} else if(in_array($table,$this->hasOne)) {

			if(isset($this->_record[$this->primaryKey]) && $this->_record[$this->primaryKey] != null) {
				if(class_exists($table)) {
					$ob = new $table;
					$c = $ob->findAllCount(array_merge(array($this->primaryKey => $this->_record[$this->primaryKey]),$search));
					return (isset($c[0]) && $c[0]->cnt > 0)?1:0;
				} else throw new ActiveRecordException("No class '$table' found");
			} else throw new ActiveRecordException("'{$this->_table}' has no record");


		} else throw new ActiveRecordException("'$table' does not belong to childs of '{$this->_table}'");
	}

	final public function addChild(TActiveRecord $child) {
		if(in_array(get_class($child),$this->hasMany)) {
			$this->_childs[] = $child;
		} else if(in_array(get_class($child),$this->hasOne)) {
			if(!isset($this->_childsOne[get_class($child)])) {
				$this->_childsOne[get_class($child)] = $child;
			} else throw new ActiveRecordException("{$this->_table} already have a child of '".get_class($child)."' which can be the only child");
		} else throw new ActiveRecordException("Class '".get_class($child)."' must belong to hasMany or hasOne property of '{$this->_table}'");
	}

	final public function assign($key,$val,$escape = true) {
		$this->_changed = true;
		if($escape) $this->_record[$key] = addslashes($val);
		else $this->_record[$key] = $val;
	}

	final public function findAll($where = null,$order = null, $page = 1, $limit = 0, $offset = 0) {
		if($order == null) $order = "`{$this->primaryKey}` asc";
		if($this->cache) {
			$sql = "select sql_cache * from `{$this->_table}`";
		} else {
			$sql = "select * from `{$this->_table}`";
		}
		if($where) {
			$sql .= ' where ';
			if(is_array($where)) {
				$parts = array();
				foreach($where as $k => $v) {
					if(strtolower($k) == 'id' && $this->mapIdToPk || strtolower($k) == 'pk') $k = $this->primaryKey;
					$parts[] = "`$k` = '".addslashes($v)."'";
				}
				$sql .= implode(' and ',$parts);
			} else if(is_string($where)) {
				$sql .= $where;
			}
		}
		$sql .= ' order by '.$order;
		if($limit > 0) {
			$page--;
			$sql .= ' limit '.($page*$offset).','.$limit;
		}
		return $this->findBySql($sql);
	}

	final public function findAllCount($where = null) {
		$sql = "select count(`{$this->primaryKey}`) as `cnt` from `{$this->_table}`";
		if($where) {
			$sql .= ' where ';
			if(is_array($where)) {
				$parts = array();
				foreach($where as $k => $v) {
					if(strtolower($k) == 'id' && $this->mapIdToPk || strtolower($k) == 'pk') $k = $this->primaryKey;
					$parts[] = "`$k` = '".addslashes($v)."'";
				}
				$sql .= implode(' and ',$parts);
			} else if(is_string($where)) {
				$sql .= $where;
			}
		}

		return $this->findBySql($sql);
	}

	final public function find($where = null, $order = null) {
		return $this->findAll($where,$order,1,1,1);
	}

	final public function findBySql($sql) {
		if($this->cacheObjects) $hash = md5($sql);

		if($this->cacheObjects && isset(self::$_objectCache[$hash])) {
			
			$ob = self::$_objectCache[$hash];
		} else {
			$res = $this->_execute($sql);
			$return = array();
			if($res->hasData()) {
				while($res->next()) {
					$fields = $res->getRecord()->getFieldsArray();
					//if(isset($fields['id']) && $fields['id'] != null) {
						$name = $this->_table;
						$n = new $name;
						foreach($fields as $field => $value) {
							$n->assign($field,$value,false);
						}
						$return[] = $n;
					//}
				}
			}

			$ob = new ArrayObject($return);
			if($this->cacheObjects) {
				$hash = md5($sql);
				self::$_objectCache[$hash] = $ob;
			}
		}

		return $ob->getIterator();
	}

	final public function findByPk($value, $order = null) {
		return $this->find(array($this->primaryKey => $value), $order);
	}

	final public function findAllByPk($value, $order = null, $page = 1, $limit = 0, $offset = 0) {
		return $this->findAll(array($this->primaryKey => $value), $order, $page, $limit, $offset);
	}

	final public function findFirst() {
		$r = $this->findAll(null,"`{$this->primaryKey}` asc",1,1,1);
		if(isset($r[0])) return $r[0];
		return null;
	}

	final public function findLast() {
		$r = $this->findAll(null,"`{$this->primaryKey}` desc",1,1,1);
		if(isset($r[0])) return $r[0];
		return null;
	}

	final public function delete() {
		if(count($this->_record) > 0) {
			$this->raiseEvent('delete',array($this->_record));
			foreach(self::$_objectCache as $k => $v) {
				if(self::$_objectCache === $this) {
					unset(self::$_objectCache[$k]);
					break;
				}
			}
			foreach($this->hasMany as $k => $v) {
				if(is_numeric($k)) {
					$childs = $this->getChild($v);
				} else $childs = $this->getChild($k);

				foreach($childs as $child) $child->delete();
			}
			foreach($this->hasOne as $v) {
				$r = $this->getChild($v);
				if($r != null) $r->delete();
			}
			$this->_execute("delete from `{$this->_table}` where `{$this->primaryKey}` = '".$this->_record[$this->primaryKey]."'");
			return true;
		}
		return false;
	}

	final public function save() {
		if(count($this->_record) > 0) {

			if($this->_changed) {
				foreach($this->_record as $field => $value) {
					if(!isset(self::$_struct[$this->_table][$field])) throw new ActiveRecordException("Field '$field' does not exist in table '{$this->_table}'");
				}

				if(!isset($this->_record[$this->primaryKey]) || $this->_record[$this->primaryKey] < 1) {
					$this->raiseEvent('insert',array($this->_record));
					$this->_execute("insert into `{$this->_table}`(`".implode('`,`',array_keys($this->_record))."`) values('".implode("','",$this->_record)."')");
					$this->_record[$this->primaryKey] = $this->_execute("select max(`{$this->primaryKey}`) as `max` from `{$this->_table}`")->get('max');
				} else if(count($this->_changedFields) > 0) {
					$this->raiseEvent('update',array($this->_record));
					$query = "update `{$this->_table}` set";
					$parts = array();
					foreach($this->_record as $field => $value) {
						//$parts[] = " `$field` = '".self::$_db->escapeString($value)."'";
						if(in_array($field,$this->_changedFields)) {
							$parts[] = " `$field` = '".addslashes($value)."'";
						}
					}
					$query .= implode(',',$parts)." where `{$this->primaryKey}` = '{$this->_record[$this->primaryKey]}'";
					$this->_execute($query);
				}

				$this->_changed = false;
				$this->_changedFields = array();

			}
			
			if(isset($this->_record[$this->primaryKey])) {
				foreach($this->_childs as $child) {
					if(get_class($child) == $this->_table) {
						$fn = $this->primaryKey.'_';
					} else {
						$fn = $this->primaryKey;
					}
					$child->$fn = $this->_record[$this->primaryKey];
					$child->save();
				}
				foreach($this->_childsOne as $k => $child) {
					$ch = $this->getChild($k);
					if(get_class($child) == $this->_table) {
						$fn = $this->primaryKey.'_';
					} else {
						$fn = $this->primaryKey;
					}
					if($ch == null) {
						$child->$fn = $this->_record[$this->primaryKey];
						$child->save();
					} else {
						foreach($child->getRecord() as $k => $v) {
							if($k != $fn) {
								$ch->$k = $v;
							}
						}
						$ch->save();
						$child = $ch;
					}
				}
			} else throw new ActiveRecordException("Cannot save childs of empty parent '{$this->_table}'");


		} else throw new ActiveRecordException("Nothing to save into table '{$this->_table}'");

	}

	final public function __call($method,$args) {
		if(eregi('^findAllBy',$method)) {
			$method = substr($method,9);
			$sql = $this->_buildQueryFromMethod($method,$args);
			$sql .= " order by `{$this->primaryKey}` asc";
		} else if(eregi('^findBy',$method)) {
			$method = substr($method,6);
			$sql = $this->_buildQueryFromMethod($method,$args);
			$sql .= " order by `{$this->primaryKey}` asc limit 0,1";
		}
		if(isset($sql)) {
			return $this->findBySql($sql);
		} else throw new ActiveRecordException("Call to undefined TActiveRecord method: '$method'");
	}

	final private function _buildQueryFromMethod($method,$args) {
		$method = str_replace('_Or_','Or',$method);
		$method = str_replace('_And_','And',$method);
		if($method[0] == '_') $method = substr($method,1);

		if($this->cache) {
			$sql = "select sql_cache * from `{$this->_table}` where";
		} else {
			$sql = "select * from `{$this->_table}` where";
		}

		$ands = explode('And',$method);
		$arg = 0;

		foreach($ands as $k => $and) {
			$ors = explode('Or',$and);
			if(count($ors) > 1) {
				foreach($ors as $k2 => $or) {
					$p = (isset($ors[$k2+1]))?'or':'and';
					if(!isset($args[$arg])) throw new ActiveRecordException("Too less arguments for method '$method'");
					if(strtolower($or) == 'id' && $this->mapIdToPk || strtolower($or) == 'pk') $or = $this->primaryKey;
					$sql .= " `$or` = '".addslashes($args[$arg])."' ".$p;
					$arg++;
				}
			} else {
				if(!isset($args[$arg])) throw new ActiveRecordException("Too less arguments for method '$method'");
				if(strtolower($and) == 'id' && $this->mapIdToPk || strtolower($and) == 'pk') $and = $this->primaryKey;
				$sql .= " `$and` = '".addslashes($args[$arg])."' and";
				$arg++;
			}
		}
		return substr($sql,0,-3);
	}

	final private function _execute($query) {
		$type = strtoupper(substr($query,0,strpos($query,' ')));

		$this->raiseEvent('executeQuery',array($type,$this->_record,$query));
		return self::$_db->query($query);
	}

	final public function __get($key) {
		if(strtolower($key) == 'id' && $this->mapIdToPk || strtolower($key) == 'pk') $key = $this->primaryKey;
		if(isset($this->_record[$key])) return stripslashes($this->_record[$key]);
		return null;
	}

	final public function __set($key,$val) {
		if(strtolower($key) == 'id' && $this->mapIdToPk || strtolower($key) == 'pk') $key = $this->primaryKey;
		$this->assign($key,$val);
		if(!in_array($key,$this->_changedFields)) $this->_changedFields[] = $key;
	}

	final public function __isset($key) {
		if(strtolower($key) == 'id' && $this->mapIdToPk || strtolower($key) == 'pk') $key = $this->primaryKey;
		return isset($this->_record[$key]);
	}

	/* TActiveRecord supports the following events:

	onExecuteQuery event is fired when the query is prepared and executed,
	$type can have the following contents: INSERT, UPDATE, SELECT,
	$record is an array of values to be inserted or updated
	$query contains the prepared query */

	public function onExecuteQuery($type,$record,$query) {
		
	}

	final public function begin() {
		self::$_db->query('begin');
	}

	final public function commit() {
		self::$_db->query('commit');
	}

	final public function rollback() {
		self::$_db->query('rollback');
	}

	public function onDelete($record) { }

	public function onUpdate($record) { }

	public function onInsert($record) { }
	
}
?>