<?php

require_once 'text.class.php';

class password extends text {
	public function getHtml(HtmlContext $context) {
		parent::getHtml($context);
		return $this->applyTemplate('password.html',
			array('name' => $this->state['name'], 'value' => htmlspecialchars($this->value)),
			$context);
	}
}

?>