<?php
require_once 'crud/RecordCrudController.abstract.php';
require_once 'Affiliate.class.php';

class AffiliatesIndexController extends RecordCrudController {
	protected $recordClass = 'Affiliate';
		
	/*protected $toolbar = array(
		'add'    => array('url' => 'create', 'class' => 'add', 'title' => 'Toevoegen'),
		'delete' => array('url' => 'delete', 'class' => 'delete', 'title' => 'Verwijderen', 'check' => true),
			);*/
			
	protected $form = 'affiliate.form';
	
	protected function preList() {
		$this->addHeader('name', 'Naam');
		$this->addHeader('contact', 'Contactpersoon');
		$this->addHeader('email', 'E-mailadres');
		$this->addHeader('phone_number', 'Telefoonnummer');

		$this->sortData['column'] = 'name';
	}
}
