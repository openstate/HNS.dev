<?php

/*
	Lexer for query expressions
*/
class QueryLexer {
	protected static $tokens = array(
		'and' => 'AND',
		'or' => 'OR',
		'not' => 'NOT',
		'like' => 'LIKE',
		'=' => 'EQ',
		'!=' => 'NE',
		'>' => 'GT',
		'>=' => 'GE',
		'<' => 'LT',
		'<=' => 'LE',
		'+' => 'ADD',
		'-' => 'SUB',
		'*' => 'MUL',
		'/' => 'DIV',
		'^' => 'EXP',
		'||' => 'CONCAT',
		'mod' => 'MOD',
		'in' => 'IN',
		'elem' => 'ELEM',
		'match' => 'MATCH',
		'at' => 'AT',
		'%' => 'PERCENT',
		',' => 'COMMA',
		'.' => 'DOT',
		'(' => 'PAREN_OPEN',
		')' => 'PAREN_CLOSE',
		'[' => 'BRACKET_OPEN',
		']' => 'BRACKET_CLOSE',
		'/^[a-z_][a-z0-9_.]*(?<!\.)/' => 'IDENT',
		'/^"[a-z_][^"]*"/' => 'QUOTED_IDENT',
		"/^'[^']*(\\\\'[^']*)*'/" => 'VALUE',
		"/^[0-9]+(\.[0-9]+)?/" => 'VALUE',
		'/^\s+/' => 0,
	);
	
	/* Tokenize an expression string */
	public static function lex($s) {
		$len = strlen($s);
		$lc = strtolower($s);
		$tokens = array();
		while (strlen($lc)) {
			/* Iterate over the input string */
			$found = false;
			foreach (self::$tokens as $expr => $token) {
				if (strlen($expr) > 1 && $expr[0] == '/') {
					/* This token is a regex, try to match it */
					if (preg_match($expr, $lc, $match)) {
						/* Token matched, consume it */
						if ($token) $tokens[] = array($token, substr($s, 0, strlen($match[0])));
						$lc = substr($lc, strlen($match[0]));
						$s = substr($s, strlen($match[0]));
						$found = true;
						break;
					}
				} else {
					/* This token is a literal, try to match it */
					if (!strncmp($lc, $expr, strlen($expr))) {
						/* Token matched, consume it */
						if ($token) $tokens[] = array($token, substr($s, 0, strlen($expr)));
						$lc = substr($lc, strlen($expr));
						$s = substr($s, strlen($expr));
						$found = true;
						break;
					}
				}
			}
			if (!$found)
				throw new ParseException('Unknown token at position '.($len - strlen($lc) + 1));
		}
		return $tokens;
	}
}

?>