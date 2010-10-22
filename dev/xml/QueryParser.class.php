<?php

/*
	Parser for query expressions
*/
class QueryParser {
	/* Parse an expression according to a root rule */
	public static function parse($s, $root) {
		$parser = new QueryParser($s);

		/* Parse the expression */
		$result = $parser->$root();

		/* Verify all tokens have been consumed */
		if ($parser->current)
			throw new ParseException('EOL expected, '.$parser->current[0].' found');
		return $result;
	}

	protected function __construct($s) {
		require_once('QueryLexer.class.php');
		require_once('QueryExpression.class.php');

		/* Tokenize the expression */
		$this->tokens = QueryLexer::lex($s);
		$this->consume();
	}

	protected $tokens;
	protected $current;

	/* Consume the next token */
	protected function consume() {
		$this->current = array_shift($this->tokens);
		if ($this->current === null)
			throw new ParseException('Unexpected end of expression');
	}

	/* Match a token and fail if it isn't matched */
	protected function expect($token) {
		/* Not matched if we're at EOL */
		if ($this->current === null)
			throw new ParseException('Unexpected end of expression');
		if ($token == $this->current[0]) {
			/* Matched, consume token */
			$result = $this->current[1];
			$this->current = array_shift($this->tokens);
			return $result;
		} else
			/* Not matched */
			throw new ParseException($token.' expected, '.$this->current[0].' found');
	}

	/* Match a token but don't fail if it isn't matched */
	protected function accept($token) {
		/* Not matched if we're at EOL */
		if ($this->current === null)
			return false;
		if ($token == $this->current[0]) {
			/* Matched, consume token */
			$result = $this->current[1];
			$this->current = array_shift($this->tokens);
			return $result;
		} else
			/* Not matched */
			return false;
	}

	/* Look ahead to the nth next token */
	protected function lookahead($count) {
		if (!$count) return $this->current[0];
		$token = reset(array_slice($this->tokens, $count-1, 1));
		return $token[0];
	}

	/* Return the first word of the first text-containing token */
	protected function firstToken() {
		while(!$value = $this->current[1])
			$this->consume();
		$this->current = null;
		return reset(array_reverse(explode('.', reset(explode(' ', $value)))));
	}

	/* Convenience function for left associative operators
	   Implements <leftAssociative> ::= <child> { (operators[0] | operators[1] | ... | operators[n]) <child> } */
	protected function leftAssociative($child, $operators) {
		$result = $this->$child();
		$op = $this->lookahead(0);
		while (in_array($op, $operators, true)) {
			/* Operator found, keep iterating */
			$this->consume();
			$term = $this->$child();
			/* Create a function syntax tree element of the current result and the new term */
			$result = new QueryFunction(strtolower($op), array($result, $term));
			$op = $this->lookahead(0);
		}
		return $result;
	}

	/* <disjunction> ::= <conjunction> { "or" <conjunction> } */
	protected function disjunction() {
		return $this->leftAssociative('conjunction', array('OR'));
	}

	/* <conjunction> ::= <conditional> { "and" <conditional> } */
	protected function conjunction() {
		return $this->leftAssociative('conditional', array('AND'));
	}

	/* <conditional> ::= "not" <conditional>
	                   | "(" <disjunction> ")"
	                   | "MATCH" ident "[" expression "]" [ "AT" value [ "%" ] ]
	                   | "ELEM" ident "[" expression "]" "." ident
	                   | <expression> ("=" | "!=" | ">" | "<" | ">=" | "<=" | "LIKE") <expression>
	                   | <expression> "IN" <list>
	                   | <expression> "IN" ident */
	protected function conditional() {
		if ($this->accept('NOT'))
			return new QueryFunction('not', array($this->conditional()));
		elseif ($this->accept('PAREN_OPEN')) {
			$result = $this->disjunction();
			$this->expect('PAREN_CLOSE');
			return $result;
		} elseif ($this->accept('MATCH')) {
			$table = $this->expect('IDENT');
			$this->expect('BRACKET_OPEN');
			$index = $this->expression();
			$this->expect('BRACKET_CLOSE');
			$weight = null;
			if ($this->accept('AT')) {
				$weight = $this->expect('VALUE');
				if ($this->accept('PERCENT'))
					$weight /= 100.0;
			}
			return new QueryFunction('match', array($table, $index) + ($weight !== null ? array('weight' => $weight) : array()));
		} elseif ($this->accept('ELEM')) {
			$table = $this->expect('IDENT');
			$this->expect('BRACKET_OPEN');
			$index = $this->expression();
			$this->expect('BRACKET_CLOSE');
			$this->expect('DOT');
			$key = $this->expect('IDENT');
			return new QueryFunction('elem', array($table, $index, $key));
		} else {
			$result = $this->expression();
			$op = $this->lookahead(0);
			if (in_array($op, array('EQ', 'NE', 'GT', 'LT', 'GE', 'LE', 'LIKE'), true)) {
				$this->consume();
				$term = $this->expression();
				return new QueryFunction(strtolower($op), array($result, $term));
			} elseif ($op == 'IN') {
				$this->consume();
				if ($this->lookahead(0) == 'PAREN_OPEN') {
					$list = $this->list_(false);
					return new QueryFunction(strtolower($op), array($result, $list));
				} else {
					$ident = $this->expect('IDENT');
					return new QueryFunction(strtolower($op), array($result, new QueryProperty($ident)));
				}
			} else
				return $result;
		}
	}

	/* <expression> ::= <term> { ("+" | "-" | "||") <term> } */
	protected function expression() {
		return $this->leftAssociative('term', array('ADD', 'SUB', 'CONCAT'));
	}

	/* <term> ::= <factor> { ("*" | "/" | "MOD") <factor> } */
	protected function term() {
		return $this->leftAssociative('factor', array('MUL', 'DIV', 'MOD'));
	}

	/* <factor> ::= <terminal> [ "^" <factor> ] */
	protected function factor() {
		$base = $this->terminal();
		if ($this->accept('EXP')) {
			$exp = $this->factor();
			return new QueryFunction('exp', array($base, $exp));
		} else
			return $base;
	}

	/* <terminal> ::= "-" <terminal>
	                | "(" <expression> ")"
	                | quoted-ident | value
	                | ident [ <paramlist> ] */
	protected function terminal() {
		if ($this->accept('SUB'))
			return new QueryFunction('neg', array($this->terminal()));
		elseif ($this->accept('PAREN_OPEN')) {
			$result = $this->expression();
			$this->expect('PAREN_CLOSE');
			return $result;
		} elseif ($quoted = $this->accept('QUOTED_IDENT'))
			/* Quoted idents are always properties */
			return new QueryProperty(substr($quoted, 1, -1));
		elseif (($value = $this->accept('VALUE')) !== false)
			return new QueryValue($value);
		else {
			/* Idents can be properties or function names */
			$ident = $this->expect('IDENT');
			if ($this->lookahead(0) == 'PAREN_OPEN') {
				$params = $this->list_(true);
				return new QueryFunction($ident, $params);
			} else
				return new QueryProperty($ident);
		}
	}

	/* <list> ::= "(" [ <expression> { "," <expression> } ] ")"
	   <paramlist> ::= "(" [ (ident "=" value | <expression>) { "," (ident "=" value | <expression> } ] ")" */
	protected function list_($isParamList) {
		$params = array();
		$this->expect('PAREN_OPEN');
		while (true) {
			/* In a paramlist, expressions can be distinguished from keywords
			   because expressions can never contain "=" */
			if ($this->lookahead(1) == 'EQ' && $isParamList) {
				$key = $this->expect('IDENT');
				$this->expect('EQ');
				$value = $this->expect('VALUE');
				$params[$key] = $value;
			} else
				$params[] = $this->expression();
			if ($this->accept('PAREN_CLOSE'))
				break;
			$this->expect('COMMA');
		}
		return $params;
	}
}

?>