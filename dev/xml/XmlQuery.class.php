<?php

/*
	Parse XML-formatted queries to <Query> objects.
*/
class XmlQuery {
	protected $xml = null;
	protected $sql = null;
	
	public $rootTag = 'query';

	/* Parse a query in XML format into a <Query> object */
	public static function parse($xml) {
		require_once('Query.class.php');
		$parser = new XmlQuery($xml);
		return $parser;
	}

	protected function __construct($xml) {
		$this->xml = $xml;
	}

	/* Delegate parsing to the appropriate function */
	public function parseXml() {
		$xml = new SimpleXmlElement($this->xml);
		$func = 'parse'.ucfirst($xml->getName());
		if (method_exists($this, $func)) {
			$this->rootTag = $xml->getName();
			return $this->$func($xml);
		} else
			throw new ParseException('Unknown command '.$xml->getName());
	}

	/* Return the first child of an xml node or its contents if it is a terminal node */
	protected function firstChild($xml) {
		foreach ($xml as $child)
			return $child;
		return (string) $xml;
	}
	
	/* Create a conjunction based on the attributes in an xml tag */
	protected function attributeConjunction($xml) {
		require_once('QueryExpression.class.php');
		
		$eqs = array();
		foreach ($xml->attributes() as $key => $value) {
			/* Create an equivalence check for the key/value pair */
			$eqs[] = new QueryFunction('eq', array(
				new QueryProperty(str_replace('"', '', (string) $key)),
				new QueryValue($this->parseValue($value))
			));
		}
		if ($eqs)
			/* Conjoin equivalence checks */
			return new QueryFunction('and', $eqs);
		else
			/* Where clause is required */
			throw new ParseException('Where clause expected but not found');
	}

	/* Get the main insert/update in a composite query */
	protected function getMainQuery($composite) {
		$queries = array_filter($composite->queries, create_function('$q', '
			return ($q instanceof InsertQuery || $q instanceof UpdateQuery) && strpos($q->table, "_") === false;
		'));
		$q = array_pop($queries);
		return $q;
	}

	/* Parse a set of insert queries */
	protected function parseInsert($xml) {
		$composite = new CompositeQuery();
		foreach ($xml->children() as $node)
			/* Merge single insert query */
			$composite->merge($this->parseSingleInsert($node));
		return $composite;
	}
	
	/* Parse a single xml insert query
	   (Can yield multiple 'sql' insert queries) */
	protected function parseSingleInsert($xml) {
		$composite = new CompositeQuery();
		$junctions = new CompositeQuery();
		$query = new InsertQuery();
		
		/* Target table */
		$query->table = (string) $xml->getName();
		
		if ($xml->xpath('select') || $xml->xpath('select-all')) {
			/* Insert-select query: parse the select part */
			$query->query = $this->parseQuery($xml);
		} else {
			/* Plain insert query: parse the children */
			foreach ($xml->children() as $key => $value) {
				if (count($value))
					/* Child has children as well: foreign references */
					foreach ($value as $item) {
						/* Create junction table insert query */
						$junction = new InsertQuery();
						$junction->table = $query->table.'::'.$key;
					
						/* Store local id as reference to this query's autoincrement id */
						$junction->fields['local_id'] = &$query->id;
					
						$id = (string) $item['id'];
						if (!$id) {
							/* New object: generate insert query and merge with queries */
							$subqueries = $this->parseSingleInsert($item);
							$composite->merge($subqueries);
							$main = $this->getMainQuery($subqueries);
							
							/* Store remote id as reference to the new object's autoincrement id */
							$junction->fields['foreign_id'] = &$main->id;
						} else {
							/* Exisiting object, store remote id */
							$junction->fields['foreign_id'] = $id;
						}
						/* Store xml attributes as fields in the junction table */
						foreach($item->attributes() as $field => $val)
							if ($field != 'id')
								$junction->fields[str_replace('"', '', $field)] = $this->parseValue($val);
								
						$junctions->add($junction);
					}
				else
					/* Add child as field to the insert query */
					$query->fields[str_replace('"', '', (string) $key)] = $this->parseValue($value);
			}
		}
		/* Add the top-level query and the junction table inserts */
		$composite->add($query);
		$composite->merge($junctions);
		
		return $composite;
	}

	/* Parse a set of update queries */
	protected function parseUpdate($xml) {
		$composite = new CompositeQuery();
		foreach ($xml->children() as $node)
			/* Merge single update query */
			$composite->merge($this->parseSingleUpdate($node));
		return $composite;
	}
	
	/* Parse a single xml update query
	   (Can yield multiple 'sql' update, insert and/or delete queries) */
	protected function parseSingleUpdate($xml) {
		$composite = new CompositeQuery();
		$junctions = new CompositeQuery();
		$query = new UpdateQuery();
		
		/* Target table */
		$query->table = (string) $xml->getName();
		
		/* Parse any where clauses present */
		foreach ($xml->xpath('where') as $where)
			$query->where[] = $this->parseExpressionTree($where, 'disjunction');
		
		/* Otherwise, parse the where clause from the attributes */
		if (!$query->where)
			$query->where[] = $this->attributeConjunction($xml);
			
		foreach ($xml->children() as $key => $value) {
			/* Parse child tags (ignore where tags, since they have already been handled */
			if ($key != 'where') {
				if (is_string($child = $this->firstChild($value)))
					/* Terminal nodes are considered values */
					$query->fields[str_replace('"', '', (string) $key)] = $this->parseValue($child);
				else {
					/* Check to see whether this is a foreign reference list or an expression */
					$reference = true;
					if (count($value) == 1)
						try {
							/* If the node has one child, try to parse it as an expression */
							$query->fields[str_replace('"', '', (string) $key)] = $this->parseExpressionTree($value, 'expression');
							$reference = false;
						} catch (ParseException $e) {
							/* Not an expression, parse as a foreign reference list */
							$reference = true;
						}
					if ($reference)
						foreach ($value as $item) {
							/* Parse foreign references */
							require_once('QueryExpression.class.php');
							
							/* Find the relevant local ids using a subquery */
							$subquery = new SelectQuery();
							$subquery->select['local_id'] = new QueryProperty('id');
							$subquery->from = $query->table;
							$subquery->where = $query->where;
							
							if (@$item['delete']) {
								/* Reference is marked to be deleted, create a delete query */
								$junction = new DeleteQuery();
								$junction->table = $query->table.'::'.$key;
								$junction->subqueries['local_id'] = $subquery;
								
								/* Deleting a reference is always based on id */
								$id = (string) $item['id'];
								if (!$id)
									throw new ParseException('Id is required for delete');
								
								/* Generate junction table where clause to match on id */
								$junction->where = array(new QueryFunction('eq', array(
									new QueryProperty('foreign_id'),
									new QueryValue($this->parseValue($id))
								)));
							} else {
								/* Add foreign reference */
								$junction = new InsertQuery();
								$junction->table = $query->table.'::'.$key;
								$junction->query = $subquery;
								$id = (string) $item['id'];
								if (!$id) {
									/* New object: generate insert query and merge with queries */
									$subqueries = $this->parseSingleInsert($item);
									$composite->merge($subqueries);
									$main = $this->getMainQuery($subqueries);
									/* Store remote id as reference to the new object's autoincrement id */
									$junction->query->select['foreign_id'] = new QueryValue(null);
									$junction->query->select['foreign_id']->value = &$main->id;
								} else {
									/* Store remote id */
									$junction->query->select['foreign_id'] = new QueryValue($id);
								}
								/* Store xml attributes as fields in the junction table */
								foreach($item->attributes() as $field => $value)
									if ($field != 'id')
										$junction->query->select[str_replace('"', '', $field)] = new QueryValue($this->parseValue($value));
							}
							$junctions->add($junction);
						}
				}
			}
		}
		/* Add the top-level query and the junction table queries */
		$composite->add($query);
		$composite->merge($junctions);
		return $composite;
	}
	
	/* Parse a set of delete queries */
	protected function parseDelete($xml) {
		$composite = new CompositeQuery();
		foreach ($xml->children() as $node)
			/* Add single delete query */
			$composite->add($this->parseSingleDelete($node));
		return $composite;
	}

	/* Parse a delete query */
	protected function parseSingleDelete($xml) {
		$query = new DeleteQuery();
		
		/* Target table */
		$query->table = (string) $xml->getName();
		
		/* Parse any where clauses present */
		foreach ($xml->xpath('where') as $where)
			$query->where[] = $this->parseExpressionTree($where, 'disjunction');
			
		/* Otherwise, parse the where clause from the attributes */
		if (!$query->where)
			$query->where[] = $this->attributeConjunction($xml);
			
		return $query;
	}

	/* Parse a select query */
	protected function parseQuery($xml) {
		/* If we have a hash tag, check cache file existance and return file hash */
		foreach ($xml->xpath('hash') as $hash) {
			$hash = (string) $hash;
			$cacheFile = $_SERVER['DOCUMENT_ROOT'].'/../'.ApiCall::$cacheDir.'/'.$hash.'.xml';
			if (!file_exists($cacheFile))
				throw new ParseException('Cached query not found');
			if (filemtime($cacheFile) < time() - ApiCall::$cacheDuration)
				throw new ParseException('Cached query expired');
			return $hash;
		}

		$query = new SelectQuery();

		/* If we have a select all tag, use that and ignore other select tags */
		foreach ($xml->xpath('select-all') as $select)
			$query->select = '*';
			
		/* Otherwise, parse select tags */
		if (!$query->select) {
			foreach ($xml->xpath('select') as $select) {
				/* Parse alias property and expression */
				$alias = str_replace('"', '', (string) $select['alias']);
				$expr = $this->parseExpressionTree($select, 'expression');

				if (!$alias) {
					/* No alias present, so generate one from the first token of the expression */
					$alias = $this->parseExpressionTree($select, 'firstToken');
					$alias = preg_replace('/[^a-z]+/', '_', strtolower($alias));
					if (!$alias) $alias = 'column';
				};
				$query->select[$alias] = $expr;
			}
		}
		
		/* Parse from tag (only one source table allowed) */
		foreach ($xml->xpath('from') as $from)
			$query->from = (string) $from;
		
		/* Parse where expressions */
		foreach ($xml->xpath('where') as $where)
			$query->where[] = $this->parseExpressionTree($where, 'disjunction');
		
		/* Parse order expressions */
		require_once('QueryExpression.class.php');
		foreach ($xml->xpath('order') as $order) {
			$dir = (string) $order['dir'];
			if (!$dir) $dir = 'asc';
			if (!in_array(strtolower($dir), array('asc', 'desc')))
				throw new ParseExpression('Invalid direction attribute \''.$dir.'\' in order clause');
			$query->order[] = new QueryOrder($this->parseExpressionTree($order, 'expression'), $dir);
		}
		
		/* Parse limit and offset */
		foreach ($xml->xpath('limit') as $limit)
			$query->limit = (int) (string) $limit;
		foreach ($xml->xpath('offset') as $offset)
			$query->offset = (int) (string) $offset;
		
		return $query;
	}
	
	/* Parse xml expression tree */
	protected function parseExpressionTree($xml, $rule) {
		/* Build string expression from xml tag structure */
		$expr = $this->parseExpression($this->firstChild($xml));
		
		/* Parse string expression according to the given top level rule */
		require_once('QueryParser.class.php');
		return QueryParser::parse($expr, $rule);
	}

	/* Parse xml expression */
	protected function parseExpression($xml) {
		/* Return text nodes unchanged */
		if (is_string($xml)) return $xml;
		
		/* Dispatch based on node name */
		switch ($xml->getName()) {
			case 'p': return $this->parseProperty($xml);
			case 'l': return $this->parseList($xml);
			case 'v': return $this->parseValue($xml);
			case 'e': return '('.$this->parseExpression($this->firstChild($xml)).')';
			default:  return $this->parseFunction($xml);
		}
	}

	/* Parse property node */
	protected function parseProperty($xml) {
		/* Remove double quotes from property name and wrap in double quotes */
		return '"'.str_replace('"', '', (string) $xml).'"';
	}

	/* Parse list node */
	protected function parseList($xml) {
		$els = array();
		/* Parse individual nodes as expressions */
		foreach ($xml->children() as $exp)
			$els[] = $this->parseExpression($exp);
			
		/* No children found, so parse as single text node with list contents */
		if (!$els)
			$els[] = $this->parseValue((string) $xml);
			
		/* Return string representation */
		return '('.implode(', ', $els).')';
	}
	
	/* Parse value node */
	protected function parseValue($xml) {
		$val = (string) $xml;
		
		/* Quote value unless it's an int or float */
		return preg_match('/^[0-9]+(\.[0-9]+)?$/', $val) ? $val :
			"'".str_replace("'", "\\'", $val)."'";
	}

	/* Parse function node */
	protected function parseFunction($xml) {
		/* Function name is stored as tag name */
		$func = (string) $xml->getName();
		/*
		if (in_array($func, $this->aggregates))
			if ($this->aggregateActive)
				throw new Exception('Trying to nest aggregate "'.$func.'" in active aggregate "'.$this->activeAggregate.'"');
			else if (!$this->selectActive)
				throw new Exception('Trying to use aggregate "'.$func.'" in non-select expression');
			else
				$this->aggregateActive = $func;
		*/
		
		/* Parse child nodes as function parameters */
		$paramSql = $keywordSql = array();
		foreach ($xml->children() as $param)
			$paramSql[] = $this->parseExpression($param);
			
		/* Parse attributes as keyword parameters */
		if (!$paramSql) $paramSql[] = (string) $xml;
		foreach ($xml->attributes() as $key => $param)
			$keywordSql[] = $key.'='.$this->parseValue($param);
		
		if (in_array($func, array('and', 'or', 'in', 'mod', 'like')))
			/* And, or, in and mod are tokens and the parser will choke if these are left as function names.
			   So, render them as the equivalent infix notation */
			return '('.implode(' '.$func.' ', $paramSql).')';
		elseif ($func == 'match')
			/* Match is a token as well */
			return $func.($paramSql ? ' '.$paramSql[0] : '').(array_key_exists('weight', $keywordSql) ? ' AT '.$keywordSql['weight'] : '');
		elseif ($func == 'elem')
			return $func.($paramSql ? ' '.$paramSql[0] : '');
		else
			/* Return the function's string representation */
			return $func.'('.implode(', ', array_merge($paramSql, $keywordSql)).')';
	}
}

class ParseException extends Exception { }
/*
class Politician {
	public function __call($call, $data) {
		var_dump($call, $data);
	}
}

$query = XmlQuery::parse(<<<EOXML
<query>
    <select alias="name"><concat join=" "><p>firstname</p><p>lastname</p></concat></select>
    <select><count>documents</count></select>
    <from>politicians</from>
    <where>
        <or><and>
            <eq><p>lastname</p><v>Wouter</v></eq>
            <eq><p>firstname</p><v>Bos</v></eq>
		</and><not>
            <in><p>lastname</p><l><v>1</v><v>2</v></l></in>
        </not></or>
    </where>
    <order dir="asc">lastname</order>
</query>
EOXML
);

$query = XmlQuery::parse(<<<EOXML
<insert>
    <politician>
        <select-all />
        <from>politicians</from>
        <where>id = 1</where>
    </politician>
    <politician>
		<firstname>Foo</firstname>
		<lastname>Bar</lastname>
		<documents>
			<document id="1" foo="qux" />
			<document foo="bar">
				<title>Baz</title>
			</document>
		</documents>
    </politician>
</insert>
EOXML
);

$query = XmlQuery::parse(<<<EOXML
<update>
    <politician>
        <firstname><p>lastname</p></firstname>
		<where>id = 1</where>
    </politician>
    <politician id="1">
        <firstname>newvalue</firstname>
        <documents>
            <document delete="delete" id="2" />
            <document id="33" />
            <document>
                <title>Foo</title>
            </document>
        </documents>
    </politician>
</update>
EOXML
);

$query = XmlQuery::parse(<<<EOXML
<delete>
    <politician id="1">
    </politician>
    <politician>
        <where>id = 1</where>
    </politician>
</delete>
EOXML
);


var_dump($query->toSql());
*/
?>