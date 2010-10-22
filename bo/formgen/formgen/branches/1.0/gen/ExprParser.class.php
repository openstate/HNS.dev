<?php

/*
	File: ExpressionParser
	Contains a parser for PHP-like expressions.

	The expressions this parser parses are used in conditions and generally follow PHP's syntax.
	The recognised operators and their precedence from high to low are:
	(code)
	- (unary), ~
	!, right
	* / %
	+ - .
	<< >>
	< <= > >=
	= !=
	&
	^
	|
	&&
	||
	? :
	(end)

	The differences with PHP: Strict equality operators (=== and !==) are not available, the equality
	operator is = instead of ==, and the ternary operator (?:) is left-associative instead of right.
	Braces can be used for precedence.

	Supported literals
	are:
	- strings: PHP style single-quoted
	- integers and floats
	- identifiers: Must start with an underscore or a letter, then any number of underscores, letters and digits.

	White space is ignored.
*/

require_once('ParseException.class.php');
require_once('Expression.class.php');

/*
	Class: ExprTokenizer
	Tokenizes a given expression.
*/
class ExprTokenizer {
	// Property: $expr
	// Contains the expression to tokenize
	protected $expr;
	// Property: $pos
	// The character position in the string.
	// This position is not yet looked at before any call to a tokenization function
	protected $pos;

	// Constant: EOT
	// Indicates the end of the expression.
	const EOT      = -2;
	// Constant: Error
	// Indicates an erroneous token.
	const Error    = -1;
	// Constant: Operator
	// Indicates an operator token.
	const Operator =  0;
	// Constant: String
	// Indicates a string token.
	const String   =  1;
	// Constant: Integer
	// Indicates an integer token.
	const Integer  =  2;
	// Constant: Float
	// Indicates a float token.
	const Float    =  3;
	// Constant: ID
	// Indicates an identifier token.
	const ID       =  4;
//	const Brace    = 5;

	// Property: $currToken
	// The type of token that has just been processed.
	protected $currToken = self::Error;
	// Property: $currValue
	// The associated value of the token type.
	// Only valid for <Operator>, <String>, <Integer>, <Float> and <ID>
	protected $currValue = '';

	/*
		Constructor: __construct
		Creates a new ExprTokenizer.

		Parameters:
		$expr - The expression to tokenize.

		<nextToken> is called on construction, so the first token is ready to be
		queried after construction.
	*/
	public function __construct($expr) {
		$this->expr = $expr;
		$this->pos = 0;
		$this->nextToken();
	}

	/*
		Method: nextToken
		Makes the next token of the expression available in <$currToken> and <$currValue>.
	*/
	public function nextToken() {
		// Skip whitespace
		while ($this->pos<strlen($this->expr) && preg_match('/\s/', $this->expr[$this->pos]))
			$this->pos++;
		// Read next token
		if ($this->pos>=strlen($this->expr)) {
			$this->currToken = self::EOT;
			$this->currValue = null;
			return;
		}
		$ch = $this->expr[$this->pos];
		if ($ch=='\'') { // String
			$inString = true;
			$str = '';
			$this->pos++;
			do {
				if ($this->pos>=strlen($this->expr))
					throw new ParseException('Unclosed string literal in expression: '.$this->expr);

				switch ($this->expr[$this->pos]) {
				case '\'': $inString = false; break;
				case '\\':
					if ($this->pos+1>=strlen($this->expr))
						throw new ParseException('Unclosed string literal in expression: '.$this->expr);
					switch ($this->expr[$this->pos+1]) {
					case '\\': $str.= '\\'; break;
					case '\'': $str.= '\''; break;
					default:
						$str.= substr($this->expr, $this->pos, 2);
					}
					$this->pos++;
					break;
				default:
					$str.= $this->expr[$this->pos];
				}
				$this->pos++;
			} while ($inString);
			$this->currToken = self::String;
			$this->currValue = $str;
		} else if ($ch>='0' && $ch<='9') { // Float / Integer
			$str = $ch;
			while ($this->pos+1<strlen($this->expr) && is_numeric($str.$this->expr[$this->pos+1])) {
				$this->pos++;
				$str.= $this->expr[$this->pos];
			}
			$this->pos++;

			if (preg_match('/(^[+-]?[0-9]+$)|(^0[xX][0-9a-fA-F]+$)/', $str)) { // integer
				$this->currToken = self::Integer;
				$this->currValue = (int)$str;
			} else { // Float
				$this->currToken = self::Float;
				$this->currValue = (float)$str;
			}
		} else if (preg_match('/[_a-zA-Z]/', $ch)) { // Identifier
			$str = $ch;
			while ($this->pos+1<strlen($this->expr) && preg_match('/[_a-zA-Z0-9]/', $this->expr[$this->pos+1])) {
				$this->pos++;
				$str.= $this->expr[$this->pos];
			}
			$this->pos++;
			$this->currToken = self::ID;
			$this->currValue = $str;
		} else if (preg_match('@[~+*/%.^?:=()<>&|!-]@', $ch)) { // Operator
			if      (preg_match('@[~+*/%.^?:=()-]@', $ch)) { // Single-char unambiguous operators
				$this->currValue = $ch;
			} else { // Ambiguous operators
				if ($this->pos+1<strlen($this->expr))
					$nextCh = $this->expr[$this->pos+1];
				else
					$nextCh = false;
				switch ($ch) {
				case '&': case '|':
					if ($nextCh == $ch) {
						$this->currValue = $ch.$ch;
						$this->pos++;
					} else $this->currValue = $ch;
					break;
				case '<': case '>':
					if ($nextCh == $ch) {
						$this->currValue = $ch.$ch;
						$this->pos++;
					} else if ($nextCh == '=') {
						$this->currValue = $ch.'=';
						$this->pos++;
					} else $this->currValue = $ch;
					break;
				case '!':
					if ($nextCh == '=') {
						$this->currValue = '!=';
						$this->pos++;
					} else $this->currValue = $ch;
					break;
				// No default, we handled everything at this point
				}
			}
			$this->currToken = self::Operator;
			$this->pos++;
		} else { // Error
			throw new ParseException('Invalid token at position '.($this->pos+1).' in expression: '.$this->expr);
		}
	}

	/*
		Method: __get
		Makes a few properties read-only publically available.

		The properties are as follows:
		token - <$currToken>
		value - <$currValue>
		pos   - <$pos>
	*/
	public function __get($name) {
		if ($name=='token')
			return $this->currToken;
		else if ($name=='value')
			return $this->currValue;
		else if ($name=='pos')
			return $this->pos;
		else
			throw new Exception('Unknown property '.$name);
	}
}

/*
	Class: ExprParser
	Parses an expression.
	It does this with a fairly straigh-forward recursive descent parser.

	Grammar:
	(in pseudo-BNF)
	(code)
	expr ::= ternaryExpr
	ternaryExpr ::= binaryExpr [ ? ternaryExpr : ternaryExpr ]
	binaryExpr ::= binaryExpr(lower precedence) [ binop binaryExpr(same precedence) ]
	binaryExpr(lowest precedence) ::= unaryExpr [ binop binaryExpr(lowest precedence) ]
	unaryExpr ::= [unaryOp] unaryExpr(lower precedence)
	unaryExpr(lowest precedence) ::= [unaryOp] factor
	factor ::= ( expr ) | literal | identifier
	(end)
*/
class ExprParser {
	// Group: Properties

	// Property: $token
	// Contains the <ExprTokenizer> for the expression.
	protected $token = null;

	protected $descparser;

	// Property: $binPrecedence
	// Precedence of binary operators, low to high
	static protected $binPrecedence =
		array(
			array('||'),
			array('&&'),
			array('|'),
			array('^'),
			array('&'),
			array('=', '!='),
			array('<', '<=', '>', '>='),
			array('<<', '>>'),
			array('+', '-', '.'),
			array('*', '/', '%')
		);

	// Property: $unaryPrecedence
	// Precedence of unary ops, low to high
	static protected $unaryPrecedence =
		array(
			array('!'),
			array('-', '~')
		);

	// Group: Functions
	/*
		Constructor: __construct
		Creates a new ExprParser.

		Parameters:
		$expr - The expression to parse.
	*/
	public function __construct($expr, DescParser $parser) {
		$this->token = new ExprTokenizer($expr);
		$this->descparser = $parser;
	}

	/*
		Method: parse
		Parses the expression given in the constructor.
		Call this function to obtain an expression tree representing the expression string.

		Returns:
		An <Expression>.
	*/
	public function parse() {
		$ex = $this->parseExpr();
		if ($this->token->token!=ExprTokenizer::EOT) {
			$val = $this->token->value;
			throw new ParseException('Unexpected token at position '.($this->token->pos).': '.$val);
		}
		return $ex;
	}

	// Group: Token functions
	/*
		Method: accept
		Checks whether the current token in the expression matches the given token, and consumes the token if it matches.

		Parameters:
		$token - The type of token to check for
		$value - The value of the token to check for

		Returns:
		A boolean whether the current token matches the parameters
	*/
	protected function accept($token, $value = null) {
		if ($this->token->token == $token &&
		    ($token != ExprTokenizer::Operator || $this->token->value == $value)) {
			$this->token->nextToken();
			return true;
		} else
			return false;
	}

	/*
		Method: expect
		Requires the current token in the expression to match the given token.
		The token is not consumed.

		Parameters:
		$token - The type of token to check for
		$value - The value of the token to check for

		This function throws an	exception if the token doesn't match.
	*/
	protected function expect($token, $value = null) {
		if ($this->accept($token, $value))
			return true;
		else {
			if ($this->token->token == ExprTokenizer::EOT)
				$val = 'EOT';
			else
				$val = $this->token->value;
			throw new ParseException('Unexpected token at position '.($this->token->pos).': '.$val);
		}
	}

	// Group: Nontermimal parsing functions
	/*
		Method: parseFactor
	  Parses the *factor* nonterminal.

		Returns:
		An <Expression>.
	*/
	protected function parseFactor() {
		$value = $this->token->value;
		if ($this->accept(ExprTokenizer::Operator, '(')) {
			$ex = $this->parseExpr();
			$this->expect(ExprTokenizer::Operator, ')');
			return $ex;
		} else if ($this->accept(ExprTokenizer::String) || $this->accept(ExprTokenizer::Integer) || $this->accept(ExprTokenizer::Float)) {
			return new ValueExpr($value);
		} else if ($this->accept(ExprTokenizer::ID)) {
			return new FormElExpr($this->descparser->getInput($value));
		} else {
			if ($this->token->token == ExprTokenizer::EOT)
				$val = 'EOT';
			else
				$val = $this->token->value;
			throw new ParseException('Unexpected token at position '.($this->token->pos).': '.$val);
		}
	}

	/*
		Method: parseUnary
		Parses the *unaryExpr* nonterminals.
		All precedence levels of unary operators are handled by this method.

		Parameters:
		$precedence - Indicates the precedence level to parse. See the <$unaryPrecedence> array for
		              the parsed operators.

		Returns:
		An <Expression>.
	*/
	protected function parseUnary($precedence = 0) {
		$ops = self::$unaryPrecedence[$precedence];
		if ($this->token->token == ExprTokenizer::Operator && in_array($this->token->value, $ops)) {
			$op = $this->token->value;
			$this->token->nextToken();
		} else
			$op = false;

		if ($op)
			$ex = $this->parseUnary($precedence);
		else if ($precedence == count(self::$unaryPrecedence)-1)
			$ex = $this->parseFactor();
		else
			$ex = $this->parseUnary($precedence+1);

		if ($op)
			$ex = new UnaryExpr($op, $ex);
		return $ex;
	}

	/*
		Method: parseBinary
		Parses the *binaryExpr* nonterminals.
		All precedence levels of binary operators are handled by this method.

		Parameters:
		$precedence - Indicates the precedence level to parse. See the <$binPrecedence> array for
		              the parsed operators.

		Returns:
		An <Expression>.
	*/
	protected function parseBinary($precedence = 0) {
		if ($precedence == count(self::$binPrecedence)-1)
			$left = $this->parseUnary();
		else
			$left = $this->parseBinary($precedence+1);
		$ops = self::$binPrecedence[$precedence];
		while ($this->token->token == ExprTokenizer::Operator && in_array($this->token->value, $ops)) {
			$op = $this->token->value;
			if ($op == '=') $op = '==';
			$this->token->nextToken();

			if ($precedence == count(self::$binPrecedence)-1)
				$right = $this->parseUnary();
			else
				$right = $this->parseBinary($precedence+1);

			$left = new BinaryExpr($op, $left, $right);
		}
		return $left;
	}

	/*
		Method: parseTernary
		Parses the *ternaryExpr* nonterminal.

		Returns:
		An <Expression>.
	*/
	protected function parseTernary() {
		$ex = $this->parseBinary();
		if ($this->accept(ExprTokenizer::Operator, '?')) {
			$cond = $ex;
			$left = $this->parseTernary();
			$this->expect(ExprTokenizer::Operator, ':');
			$right = $this->parseTernary();
			return new TernaryExpr($cond, $left, $right);
		} else
			return $ex;
	}

	/*
		Method: parseExpr
		Parses the *expr* nonterminal.

		Returns:
		An <Expression>.
	*/
	protected function parseExpr() {
		return $this->parseTernary();
	}
}

?>