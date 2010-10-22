<?php

require_once 'Controller.abstract.php';

require_once '../formgen/formgen/core/FormInstance.class.php';
require_once 'pager/NumberPager.class.php';

abstract class CrudController extends Controller {
	/**
	 * The number of items per page for the list action
	 *
	 * @var int
	 **/
	protected $listPageSize = 20;
	
	/**
	 * The file to use for the pager
	 *
	 * @var string
	 **/
	protected $pagerTemplate = 'crud/pager.html';
	
	/**
	 * sorting of the list, a column and a direction
	 *
	 * @var array
	 **/
	protected $sortData = array(
		'column' => 'id',
		'direction' => 'asc',
	);
	
	/**
	 * Holds the paths to the templates, always relative to the parent folder of the document_root
	 * you can overwrite these in the preDispatch, or directly in your extended class
	 *
	 * @var array
	 **/
	protected $templates = array(
		'list' => 'templates/crud/list.html',
		'edit' => 'templates/crud/edit.html',
		'create' => 'templates/crud/create.html'
	);
	
	/**
	 * The name of the pofile to use
	 *
	 * @var string|boolean
	 **/
	protected $pofile = false;
	
	/**
	 * The checkbox columns
	 *
	 * @var array
	 **/
	protected $checkColumns = array(
		'default' => '##crud.default_checkcolumn##',
	);
	
	/**
	 * The toolbar
	 *
	 * @var array
	 **/
	protected $toolbar = array(
		'add'    => array('url' => 'create', 'class' => 'add', 'title' => '##crud.add_title##'),
		'delete' => array('url' => 'delete', 'class' => 'delete', 'title' => '##crud.delete_title##', 'check' => true),
	);
	
	/**
	 * extra columns to add for each item
	 *
	 * @var array
	 **/
	protected $extraColumns = array(
		'options' => array(
			'title' => '##crud.actions##',
			'class' => 'options',
			'actions' => array(
				'edit'   => array('name' => 'edit', 'url' => 'edit/{$id}', 'class' => 'entry-edit', 'title' => '##crud.edit_title##', 'description' => '##crud.edit_description##', 'condition' => null),
				'delete' => array('name' => 'delete', 'url' => 'delete/{$id}', 'class' => 'entry-delete', 'title' => '##crud.delete_title##', 'description' => '##crud.delete_description##', 'condition' => null),
			),
		),
	);
	
	/**
	 * Holds a definition of the header columns, with sortable (bool), title (string)
	 *
	 *
	 * @var array
	 **/
	protected $headers = array();
	
	/**
	 * Holds the locations of the forms to use relative to your modules forms dir.
	 * Specifiy the path to the form to use for both edit and create or make it an
	 * assoc array with keys 'create', 'edit' and values with the paths to the differend forms.
	 * If it's false it will use $controllername.form
	 *
	 * @var string|array|boolean
	 **/
	protected $form = false;
	
	public function indexAction() {
		$this->listAction();
	}
	
	/**
	 * Displays a list.
	 *
	 * @return void
	 * @author Harro
	 **/
	public function listAction() {
		if (method_exists($this, 'preList')) $this->preList();
		$pager = new NumberPager(array('pageLength' => $this->listPageSize));
		$pager->setPage($this->request->getGet('page', 1));
		$this->view->pagerTemplate = $this->pagerTemplate;
		$this->view->pager = $pager;
		$this->view->getVars = $this->request->getGet();
		
		//sorting
		$this->sortData = array(
			'column' => $this->request->getGet('column', $this->sortData['column']),
			'direction' => $this->request->getGet('direction', $this->sortData['direction']),
		);
		$this->view->sort = $this->sortData;
		
		//get the data
		$data = $this->listData($pager);
		$this->view->rows = $data;
		
		//headers,and actions and toolbar
		$this->view->headers = $this->headers;
		$this->view->actionPrefix = '/' . $this->request->getDestination()->module . '/' .  $this->request->getDestination()->controller . '/';
		$this->view->extraColumns = $this->extraColumns;
		$this->configureToolbar();
		
		$this->setTemplate('list');
		
		$this->addLanguageFiles();
		$this->view->render();
	}
	
	/**
	 * Displays the creation form
	 *
	 * @return void
	 * @author Harro
	 **/
	public function createAction() {
		$form = $this->loadForm('create');
		if (method_exists($this, 'preCreateForm')) $this->preCreateForm($form);
		
		if ($this->request->isPost()) {
			$form->setPostData($this->request->getPost(), $this->request->getFiles());
			if ($form->isValid()) {
				$values = $form->getValues();
				$this->saveData(false, $values);
				$this->redirect($this->returnUrl()); //go back to the list action
			}
		} else {
			$form->setRawdata($this->loadData(false));
		}
		$this->addLanguageFiles();
		$this->view->form = $form;
		$this->setTemplate('create');
		$this->view->render();
	}
	
	/**
	 * Displays the edit form
	 *
	 * @return void
	 * @author Harro
	 **/
	public function editAction() {
		$form = $this->loadForm('edit');
		if (method_exists($this, 'preEditForm')) $this->preEditForm($form);
		
		$id = $this->request->getParam(0);
		if (empty($id) || !ctype_digit($id)) {
			$this->redirect($this->returnUrl()); //go back to the list action
		}
		
		if ($this->request->isPost()) {
			$form->setPostData($this->request->getPost(), $this->request->getFiles());
			if ($form->isValid()) {
				$values = $form->getValues();
				$this->saveData($id, $values);
				$this->redirect($this->returnUrl()); //go back to the list action
			}
		} else {
			$form->setRawdata($this->loadData($id));
		}
		$this->addLanguageFiles();
		$this->view->form = $form;
		$this->setTemplate('edit');
		$this->view->render();
	}
	
	/**
	 * Loads the form based on the parameters
	 *
	 * @return FormInstance
	 * @author Harro
	 **/
	protected function loadForm($type) {
		$destination = $this->request->getDestination();
		$formFile = $_SERVER['DOCUMENT_ROOT'].'/../modules/'.$destination->module.'/forms/'.$destination->controller.'.form';
		if (!file_exists($formFile)) {
			$formFile = $_SERVER['DOCUMENT_ROOT'].'/../modules/'.$destination->module.'/forms/admin/'.$destination->controller.'.form';
		}
		if ($this->form) {
			if (is_array($this->form) && isset($this->form[$type])) {
				$formFile = $_SERVER['DOCUMENT_ROOT'].'/../modules/'.$destination->module.'/forms/'.$this->form[$type];
			} else if (!is_array($this->form)) {
				$formFile = $_SERVER['DOCUMENT_ROOT'].'/../modules/'.$destination->module.'/forms/'.$this->form;
			}
		}
		return new FormInstance($formFile);
	}
	
	/**
	 * Adds the toolbar
	 *
	 * @return void
	 * @author Harro
	 **/
	protected function configureToolbar() {
		$destination = $this->request->getDestination();
		$prefix = '/' . $destination->module . '/' . $destination->controller . '/';
		$result = $this->toolbar;
		$checkPrefix = $destination->module . '_' . $destination->controller . '_' . $destination->action;
		foreach ($result as &$item) {
			if ($item['url'][0] != '/') {
				$item['url'] = $prefix . $item['url'];
			}
			if (isset($item['check'])) {
				if ($item['check'] === true) $item['check'] = 'default';
				$item['check'] = $checkPrefix . $item['check'];
			}
		}
		$checkColumns = array();
		foreach ($this->checkColumns as $name => $value) {
			if (!is_array($value)) $value = array('title' => $value);
			$checkColumns[$checkPrefix . $name] = $value;
		}
		$this->view->checkColumns = $checkColumns;
		$this->view->toolbar = $result;
	}
	
	/**
	 * Loads the templates based on the settings
	 *
	 * @param action string The action to use to load the tempalte
	 * @return void
	 * @author Harro
	 **/
	protected function setTemplate($action) {
		if (isset($this->templates[$action])) {
			$this->view->setTemplatePath(dirname($_SERVER['DOCUMENT_ROOT'].'/../'.$this->templates[$action]));
			$this->view->setTemplate(basename($this->templates[$action]));
		}
	}
	
	/**
	 * Adds the language files to the view
	 *
	 * @return void
	 * @author Harro
	 **/
	protected function addLanguageFiles() {
		if ($this->pofile) {
			if (!is_array($this->pofile)) {
				$this->addPoFile($this->pofile);
			} else {
				foreach ($this->pofile as $pf) {
					$this->addPoFile($pf);
				}
			}
		}
		$this->addPoFile('form.po', $_SERVER['DOCUMENT_ROOT'].'/../locales');
		$this->addPoFile('crud.po', $_SERVER['DOCUMENT_ROOT'].'/../locales');
	}
	
	/**
	 * Add a header
	 *
	 * @param identifier string The identifier of the column, the items in the data set should be available via this as key
	 * @param title string The title to display
	 * @param action string|bool The action to use when clicked (not used when false)
	 * @param sortable bool If the column is sortable
	 * @param value string|bool The value to use for displaying, this will be evaled, so you can use {$row.identifier|date_format} to apply the date_format modifier
	 * @return void
	 * @author Harro
	 **/
	protected function addHeader($identifier, $title, $action = false, $sortable = true, $value = false) {
		//sort by the first column added
		if (count($this->headers) === 0) {
			$this->sortData['column'] = $identifier;
		}
		$this->headers[$identifier] = array(
			'title' => $title,
			'sortable' => $sortable,
			'action' => $action,
			'value' => $value,
		);
	}
	
	/**
	 * returns the url to return to after a post
	 *
	 * @return string
	 * @author Harro
	 **/
	protected function returnUrl() {
		$return = '/' . $this->request->getDestination()->module . '/' .  $this->request->getDestination()->controller . '/';
		return $return;
	}
	
	/**
	 * Deletes one or multiple items
	 *
	 * @return void
	 * @author Harro
	 **/
	public function deleteAction() {
		$id = $this->request->getParam(0);
		if (isset($id) && preg_match('/^(\d+,)*\d+$/', $id)) {
			$this->deleteData(explode(',', $id));
		}
		$this->redirect($this->returnUrl()); //go back to the list action
	}
	
	/**
	 * Called before any list processing is done, used to configure the list display
	 *
	 * @return void
	 * @author Harro
	 **/
	abstract protected function preList();
	
	/**
	 * List function, should be implemented in the extending class
	 *
	 * @param	pager	Pager	The pager
	 * @return array	An array of the following format:
	 * 					key: primary identifiers
	 * 					value: The data as associative array with a row.
	 * @author Harro
	 **/
	abstract protected function listData($pager);
	
	/**
	 * Load one item, should be implemented in the extending class
	 *
	 * @return array Assoc array for the item to be used in a form
	 * @author Harro
	 **/
	abstract protected function loadData($id);
	
	/**
	 * Save one item, should be implemented in the extending class
	 *
	 * @return void
	 * @author Harro
	 **/
	abstract protected function saveData($id, $data);
	
	/**
	 * Delete items
	 *
	 * @param ids array The ids to delete
	 * @return void
	 * @author Harro
	 **/
	abstract protected function deleteData($ids);
}