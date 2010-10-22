<?php

require_once('{processorclass}Base.class.php');

class {processorclass} extends {processorclass}Base {
	{if:loadmany}protected $sortDefault = 'id';{/if}

	{if:load}
	public function init() {
		if (!isset($_GET['id']) || !ctype_digit($_GET['id']))
			Dispatcher::header('../');
	}
	{/if}

	public function show($smarty) {
		{if:load || loadmany}$this->loadFromObject();{/if}
		parent::show($smarty);
	}

{if:save}
	public function action() {
		$this->saveToObject();
		Dispatcher::header('../');
	}
{/if}

{customfuncs}
}

?>