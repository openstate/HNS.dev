<?php

require_once 'SessionSaveHandler.interface.php';

// TODO : database onafhankelijk, nu gebasseerd op pgsql.
class DbSessionSaveHandler implements SessionSaveHandler {
	protected $db;

	public function __construct($database) {
		$this->setDatabase($database);
	}

	public function setDatabase($database) {
		$this->db = $database;
	}

	public function getDatabase() {
		return $this->db;
	}

	public function open($path, $name) {
		return true;
	}

	public function close() {
		return true;
	}

	public function read($id) {
		$data = $this->db->query('SELECT data FROM sys_sessions WHERE id = %', $id)->fetchCell();		
		return pg_unescape_bytea($data);
	}

	public function write($id, $data) {
		$this->db->query('BEGIN');
		if ($this->db->query('SELECT id FROM sys_sessions WHERE id = % FOR UPDATE', $id)->fetchCell())
			$this->db->query('UPDATE sys_sessions SET data = %x, modified = now() WHERE id = %', $data, $id);
		else
			$this->db->query('INSERT INTO sys_sessions (id, data) VALUES (%, %x)', $id, $data);
		$this->db->query('COMMIT');
		return true;
	}

	public function destroy($id) {
		$this->db->query('DELETE FROM sys_sessions WHERE id = %', $id);
		return true;
	}

	public function gc($maxLifetime) {
		$this->db->query('DELETE FROM sys_sessions WHERE (now() - modified) > interval \'% second\'', $maxLifetime);
		return true;
	}
}