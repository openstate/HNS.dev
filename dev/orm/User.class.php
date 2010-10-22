<?php

class User extends Record {
	protected $tableName = 'usr_users';
	protected $hitsTableName = 'usr_user_hits';
	
	protected $ratePeriod = '60 minutes';

	protected $config = array(
		'name' => array(),
		'key' => array(),
		'ip' => array(),
		'contact' => array(),
		'email' => array(),
		'phone_number' => array(),
		'affiliate_id' => array(),
		'max_rate' => array(),
		'max_load' => array(),
	);
	
	public function hits() {
		return $this->db->query('
			SELECT count(*) FROM %t
			WHERE user_id = % AND timestamp >= now() - %::interval',
			$this->hitsTableName, $this->id, $this->ratePeriod)->fetchCell();
	}
	
	public function hit() {
		if (!mt_rand(0, 100))
			$this->db->query(
				'DELETE FROM %t WHERE timestamp < now() - %::interval',
				$this->hitsTableName, $this->ratePeriod);
		$this->db->query(
			'INSERT INTO %t (user_id) VALUES (%)',
			$this->hitsTableName, $this->id);
	}

}

?>