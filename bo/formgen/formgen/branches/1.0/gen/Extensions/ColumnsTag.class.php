<?php

// File: Columns Tag

require_once('DescParser.class.php');
require_once('Object.class.php');
require_once('HTML.class.php');

/*
	Class: ForeachNode
	Represents a Smarty foreach construct.
*/
class ForeachNode extends HTMLTag {
	private $from, $item, $key;

	private $elseChildren = array();

	/*
		Constructor: __construct

		Parameters:
		$from - The variable to iterate over
		$item - The name of the variable that will hold a single item
		$key  - The name of the variable that will hold the key for the current item.
	*/
	public function __construct($from, $item, $key = '') {
		parent::__construct('');
		$this->from = $from;
		$this->item = $item;
		$this->key  = $key;
	}

	public function plainClone() {
		$result = parent::plainClone();
		$newChildren = array();
		foreach ($result->elseChildren as $child)
			$newChildren[] = $child->plainClone();
		$result->elseChildren = $newChildren;
		return $result;
	}

	public function templateClone($params) {
		$res = parent::templateClone($params);
		$newChildren = array();
		foreach ($res->elseChildren as $child)
			$newChildren[] = $child->templateClone($params);
		$res->elseChildren = $newChildren;
		return $res;
	}

	/*
		Method: addElseChildren
		Adds HTML nodes to be shown if the array is empty.

		Parameters:
		$nodes - An array of <HTMLNodes> to add as children.
	*/
	public function addElseChildren(array $nodes) {
		$this->elseChildren = array_merge($this->elseChildren, $nodes);
	}

	public function getHTML() {
		$html = '{foreach from=$'.$this->from.' item='.$this->item.($this->key ? ' key='.$this->key : '')." name='dataloop'}\n";

		foreach ($this->children as $child)
			$html.= $child->getHTML();

		if (count($this->elseChildren)>0) {
			$html.= "\n{foreachelse}\n";
			foreach ($this->elseChildren as $child)
				$html.= $child->getHTML();
		}

		$html.= "\n{/foreach}\n";
		return $html;
	}
}

/*
	Class: ColumnsTag
	Extension tag that describes a column structure.

	See the manual, <Columns tag> for its use.
*/
class ColumnsTag extends ExtensionTag {
	protected $columns = array();
	protected $header, $elements;

	public function parse(DOMElement $node, DescParser &$parser) {
		$GLOBALS['formDataVar'] = 'datarow';

		$cols = $parser->getElementsByName($node, 'column');
		foreach ($cols as $col) {
			$sort = null;
			if ($col->hasAttribute('sort')) {
				$sort = $col->getAttribute('sort');
				if ($sort == '0' || $sort=='')
					$sort = false;
				$col->removeAttribute('sort');
			}

			if ($col->hasAttribute('property')) {
				// Column linked to property
				$property = $parser->getProperty($col->getAttribute('property'));

				$parser->linkInputToProperty($property->getID(), $property->getID());

				if ($sort === null)
					$sort = $property->getID();

				if (count($value = $parser->getElementsByName($col, 'value'))>0) {
					if (count($value)>1)
						throw new ParseException('Multiple values given in column for property '.$property->getID());

					$value = reset($value);
					$input = new HTMLTag('');
					$input->addChildren($parser->parseHTMLarray($value->childNodes, true));
				} else {
					if ($col->hasAttribute('type')) {
						$inputType = $col->getAttribute('type');
						$col->removeAttribute('type');
					} else
						$inputType = 'static';
					$input = HTMLInputFactory::create($inputType, $property->getID(), $parser->getFormName());
					$input->setFromProperty($property);
				}
				if ($property->getEnum() instanceof CustomEnum && $property->getEnum()->getSingleAttr()!='')
					$parser->linkInputToProperty($property->getEnum()->getSingleAttr(), $property->getEnum()->getSingleAttr());

				$newCol = array(
					'label' => new HTMLText($property->getCaption()),
					'value' => $input,
				);
				$col->removeAttribute('property');

				$attr = array();
				for ($i=0; $i<$col->attributes->length; $i++) {
					$attrnode = $col->attributes->item($i);
					if (substr($attrnode->nodeName, 0, 2)=='i_')
						$attr[substr($attrnode->nodeName, 2)] = $attrnode->nodeValue;
				}
				$input->addAttributes($attr);
				foreach ($attr as $attrName => $attrVal)
					$col->removeAttribute('i_'.$attrName);
			} else {
				// Plain column
				$label = $parser->getElementsByName($col, 'label');
				if (count($label)==0)
					throw new ParseException('Label missing in column');
				if (count($label)>1)
					throw new ParseException('Multiple labels given in column');
				$label = reset($label);

				$value = $parser->getElementsByName($col, 'value');
				if (count($value)==0)
					throw new ParseException('Value missing in column');
				if (count($value)>1)
					throw new ParseException('Multiple values given in column');
				$value = reset($value);

				$labelTag = new HTMLTag('');
				$labelTag->addChildren($parser->parseHTMLarray($label->childNodes, true));
				$valueTag = new HTMLTag('');
				$valueTag->addChildren($parser->parseHTMLarray($value->childNodes, true));

				$newCol = array('label' => $labelTag, 'value' => $valueTag);
			}

			if ($sort === null)
				$sort = false;
			$newCol['sort'] = $sort;
			$newCol['attribs'] = $col->attributes;

			$this->columns[]= $newCol;
		}

		$empty = $parser->getElementsByName($node, 'empty');
		if (count($empty)>1)
			throw new ParseException('Multiple empty tags given');
		if (count($empty)==1) {
			$empty = reset($empty);
			$emptyTag = new HTMLTag('');
			$emptyTag->addChildren($parser->parseHTMLarray($empty->childNodes, true));
		} else
			$emptyTag = null;

		$GLOBALS['formDataVar'] = 'formdata';

		$headerNodes  = array();
		$elementNodes = array();
		foreach ($this->columns as $col) {
			if ($col['sort']!==false) {
				$head = new HTMLTag('a');
				$head->addChildren(array($col['label']));
				$headAttribs = array(
					'href' => '?sortcol='.$col['sort'].'&sort={if $formsort.col==\''.$col['sort'].'\' and $formsort.dir==\'asc\'}desc{else}asc{/if}',
					'class' => '{if $formsort.col==\''.$col['sort'].'\'}current {$formsort.dir}{else}asc{/if}');
				if ($node->hasAttribute('appendquerystring') && $node->getAttribute('appendquerystring'))
					$headAttribs['href'] .= '{foreach from=$smarty.get key=key item=value name=get}{if $key != \'sortcol\' and $key != \'sort\'}&{$key|urlencode}={$value|urlencode}{/if}{/foreach}';
				$head->addAttributes($headAttribs);
			} else {
				$head = $col['label'];
			}
			$headerNodes = array_merge($headerNodes, $parser->applyTemplate('colhead', array('value' => $head), false));
			$newNode = $parser->applyTemplate('colvalue', array('value' => $col['value']), $col['attribs']->length > 0);
			if ($col['attribs']->length > 0) {
				$newNode[0]->addAttributes($col['attribs']);
			}
			$elementNodes = array_merge($elementNodes, $newNode);
		}

		$row = new HTMLTag('');
		$row->addChildren($elementNodes);

		$this->header = new HTMLTag('');
		$this->header->addChildren($headerNodes);

		$this->elements = new ForeachNode('formdata', 'datarow', 'id');
		if ($emptyTag) {
			if ($parser->hasTemplate('emptyrow'))
				$colTpl = 'emptyrow';
			else
				$colTpl = 'colrow';
			$this->elements->addElseChildren($parser->applyTemplate($colTpl, array('row' => $emptyTag), false));
		}
		$templatedRow = $parser->applyTemplate('colrow', array('row' => $row), $node->attributes->length > 0);
		if ($node->attributes->length > 0)
			$templatedRow[0]->addAttributes($node->attributes);
		$this->elements->addChildren($templatedRow);
	}
/*

		<columns>
			<table>
				<tr><header /></tr>
				<elements />
			</table>
		</columns>

		<colhead><th><value /></th></colhead>
		<colrow><tr><row /></tr></colrow>
		<colvalue><td><value /></td></colhead>

*/

	public function getHTMLNodes() {
		return array(
			'header'   => $this->header,
			'elements' => $this->elements
		);
	}

	public function getTemplateTagnames() { return array('header', 'elements'); }
	public function getExtraTemplateTags() {
		return array(
			'colhead' => array('value'),
			'colrow' => array('row'),
			'emptyrow' => array('row'),
			'colvalue' => array('value')
		);
	}
}

DescParser::registerExtension('columns', 'ColumnsTag');

?>