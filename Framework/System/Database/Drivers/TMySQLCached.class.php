<?php
class TMySQLCached extends TMySQL
{
	const SELECT_MATCH = '^(NOCACHE\s+)?\(?\s*SELECT\s+';
	const INSERT_MATCH = '^(INSERT|REPLACE)\s+INTO\s+(?P<tables>.*)(\s+VALUES|\(|\(?SELECT\s+)';
	const UPDATE_MATCH = '^UPDATE\s+(?P<tables>.*)\s+SET\s+';
	const DELETE_MATCH = '^DELETE\s+FROM\s+(?P<tables>.*)(\s+WHERE\s+|$)';
	const TRUNCATE_MATCH = '^TRUNCATE\s+TABLE\s+(?P<tables>.*)$';
	const EXPIRES = 0;
	
	public static $ENABLED = false;

	public function Query($query, array $params = array(), $expires = self::EXPIRES)
	{
		if(!self::$ENABLED)
		{
			$query = preg_replace('{^\s*NOCACHE\s+}ix', '', $query);
			
			return parent::Query($query, $params);
		}
		
		$query = $this->_PrepareSql($query, $params);
		$query = $this->_CleanQuery($query, $parts);
		
		if(preg_match('{'.self::SELECT_MATCH.'}ix', $query))
		{
			if(preg_match('{^NOCACHE\s+}ix', $query))
			{
				return parent::Query(trim(preg_replace('{^NOCACHE\s+}ix', '', $query)));
			}
			
			$query_hash = md5($query);

			if(!($res = TCache::ReadTagged(array($query_hash))))
			{
				$res = parent::Query($query);

				$tags = $this->_GetTags($res);
				
				if(isset($parts['identifiers']))
				{
					$tags = $this->_UnaliasTags($tags, $parts);
				}

				$tags[] = $query_hash;

				TCache::WriteTagged($tags, $res, $expires);

				return $res;
			}
			else
			{
				return $res[0];
			}
		}
		else if
		(
			preg_match_all('{'.self::DELETE_MATCH.'}ixU', $query, $matches) ||
			preg_match_all('{'.self::INSERT_MATCH.'}ixU', $query, $matches) ||
			preg_match_all('{'.self::UPDATE_MATCH.'}ixU', $query, $matches) ||
			preg_match_all('{'.self::TRUNCATE_MATCH.'}ixU', $query, $matches)

		) {
			$res = parent::Query($query);

			if(count($res) > 0) //affected rows > 0
			{
				$tables = array();

				foreach($matches['tables'] as $tables_list)
				{
					$e = explode(',', $tables_list);

					foreach($e as $table)
					{
						$table = trim($table); //`table`, `table` as .., table, table as

						if(preg_match_all('{`([^`]+)`}', $table, $m)) //quoted
						{
							$table = implode('.', $m[1]);
						}
						else //unquoted
						{
							if($pos = strpos($table, ' '))
							{
								$table = substr($table,0,$pos);
							}
						}

						if(!in_array($table, $tables))
						{
							$tables[] = $table;
						}
					}
				}

				TCache::DeleteTagged($tables, TCache::ANY);
			}

			return $res;
		}
		else
		{
			return parent::Query($query); //any other query
		}
	}

	private function _CleanQuery($query, &$return_parts = null)
	{
		$len = strlen($query);

		$expecting = '';
		$parts = array();
		$part = '';

		for($i = 0; $i < $len; $i++)
		{
			$letter = substr($query, $i, 1);
			$prev_letter = $i > 0 ? substr($query, $i-1, 1) : '';
			$next_letter = $i+1 < $len ? substr($query, $i+1, 1) : '';

			if($expecting == '')
			{
				if($letter == '`' || $letter == '"' || $letter == "'" && ($prev_letter != $letter && $letter != $next_letter && $prev_letter != '\\'))
				{
					$expecting = $letter;
					$parts[] = trim($part);
					$part = '';
				}

				$part .= $letter;
			}
			else if($letter == $expecting && $prev_letter != $expecting && $expecting != $next_letter && $prev_letter != '\\')
			{
				$expecting = '';
				$part .= $letter;
				$parts[] = trim($part);

				$part = '';
			}
			else
			{
				$part .= $letter;
			}
		}

		$parts[] = trim($part);

		foreach($parts as &$part)
		{
			$first = substr($part, 0, 1);
			$last = substr($part, strlen($part)-1, 1);

			if($first == $last && ($first == '`' || $first == '"' || $first == '"'))
			{
				if($return_parts)
				{
					if($first == '`')
					{
						if(!isset($return_parts['identifiers']))
						{
							$return_parts['identifiers'] = array();
						}

						$return_parts['identifiers'][] = $part;
					}
					else if(trim($part) != '')
					{
						if(!isset($return_parts['quotes']))
						{
							$return_parts['quotes'] = array();
						}

						$return_parts['quotes'][] = $part;
					}
				}
				//quoted blocks
			}
			else
			{
				$part = preg_replace('{\s+}', ' ', $part);

				if(trim($part) != '')
				{
					if(!isset($return_parts['identifiers']))
					{
						$return_parts['identifiers'] = array();
					}

					$return_parts['identifiers'][] = $part;
				}
			}
		}

		return implode(' ', $parts);
	}

	protected function _GetTags(TMySQLResult $result)
	{
		$ret = array();

		if(is_resource($result->GetResource()))
		{
			$count = mysql_num_fields($result->GetResource());

			for($i = 0; $i < $count; $i++)
			{
				$table = mysql_field_table($result->GetResource(), $i);

				if($table != null && !in_array($table, $ret))
				{
					$ret[] = $table;
				}
			}
		}

		return $ret;
	}

	protected function _UnaliasTags(array $tags, array $parts)
	{
		$tmp_a = preg_split('{UNION}i', implode(' ', $parts['identifiers']));

		$return = array();

		foreach($tmp_a as $query)
		{
			$query = substr($query, stripos($query, ' FROM '));

			if(($pos = stripos($query, ' WHERE ')) || ($pos = stripos($query, ' HAVING ')) || ($pos = stripos($query, ' ORDER ')) || ($pos = stripos($query, ' GROUP ')))
			{
				$query = substr($query, 0, $pos);
			}

			foreach($tags as $tag)
			{
				$quoted_tag = preg_quote($tag);

				$regexp = "(?P<table>([^\s]+))(?:(?:\s+{$quoted_tag}(?:\s+|,|\(|$))|(?:\s*`{$quoted_tag}`)|(?:\s+AS\s+{$quoted_tag}(?:\s+|,|\(|$))|(?:\s+AS\s*`{$quoted_tag}`))";

				if(preg_match_all('{'.$regexp.'}ixU', $query, $matches))
				{
					if(count($matches['table']) == 1 && !in_array(strtoupper($matches['table'][0]), array('JOIN', 'FROM')))
					{
						$table = str_replace('`','',$matches['table'][0]);

						if(!in_array($table, $return)) $return[] = $table;
					}
					else
					{
						if(!in_array($tag, $return)) $return[] = $tag;
					}
				}
				else
				{
					if(!in_array($tag, $return)) $return[] = $tag;
				}
			}
		}

		return $return;
	}
}