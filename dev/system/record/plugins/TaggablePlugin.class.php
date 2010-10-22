<?php

require_once 'record/plugins/RecordPlugin.abstract.php';

/* Adds a tagging system to the ORM object */
class TaggablePlugin extends RecordPlugin {
	protected $options = array(
		'field' => 'tags',            // 'Field' where tags can be read and written
		'separator' => ' ',           // Tag separator
		'tag_table' => 'tags',        // Table used to store tags
		'weight' => 1.0,              // Weight to use for tags
		'owner_id' => 1,              // Owner id to use for tags
	);
	
	protected $original = array();    // List of tags retrieved from database
	protected $tags = array();        // List of tags as modified by current query
	protected $ownTags = array();     // List of tags owned by current user
	protected $query = array();       // List of tags to insert, update or delete by current query
	
	protected function init() {
		/* Add a listener to the tags 'field' */
		$this->record->setDataListener($this->options['field'], $this);
		
		if ($pk = $this->record->getPk()) {
			/* Load the current set of tags associated with this object */
			$this->original = $this->record->db->query(
				'SELECT sum(weight), name FROM %t WHERE object_id = % AND object_table = % GROUP BY name',
				$this->options['tag_table'], $pk, $this->record->tableName
			)->fetchAllCells('name');
			asort($this->original);
			$this->tags = $this->original;
			
			/* Load the tags owned by the current user */
			$this->ownTags = $this->record->db->query(
				'SELECT weight FROM %t WHERE object_id = % AND object_table = % AND originated_from IS NULL AND owner_id = %',
				$this->options['tag_table'], $pk, $this->record->tableName, $this->options['owner_id']
			)->fetchAllCells('name');
		}
	}
	
	public function __get($name) {
		/* Return separated list of tags in order of weight */
		return implode($this->separator, array_keys($this->tags));
	}
	
	public function __set($name, $value) {
		// Find separate tags and reset instance values based on query */
		$tags = explode($this->separator, $value);
		$this->tags = $this->original;
		$this->query = array();
		
		foreach ($tags as $tag) {
			$delete = false;
			if ($tag[0] == '+')
				/* Plus prefix is optional */
				$tag = substr($tag, 1);
			elseif ($tag[0] == '-') {
				/* Minus prefix denotes removing tag */
				$tag = substr($tag, 1);
				$delete = true;
			}
			
			/* Ignore duplicate tags */
			if (array_key_exists($tag, $this->query)) continue;
			
			/* Store tag in query list */
			if (($tagExists = array_key_exists($tag, $this->ownTags)) || !$delete)
				$this->query[$tag] = $delete ? 'delete' : ($tagExists ? 'update' : 'insert');
			else
				continue;
			
			/* Modify tag weights */
			if (!array_key_exists($tag, $this->tags) && !$delete) $this->tags[$tag] = 0;
			if (($delete || $tagExists) && @$this->tags[$tag]) $this->tags[$tag] -= $this->ownTags[$tag];
			if (!$delete) $this->tags[$tag] += $this->options['weight'];
			if (array_key_exists($tag, $this->tags) && !$this->tags[$tag]) unset($this->tags[$tag]);
		}
		
		/* Resort tags */
		asort($this->tags);
	}

	public function postSave(RecordEvent $event) {
		foreach ($this->query as $key => $op) {
			switch($op) {
				case 'delete':
					/* delete tag */
					$this->record->db->query(
						'DELETE FROM %t WHERE name = % AND object_id = % AND object_table = % AND originated_from IS NULL AND owner_id = %',
						$this->options['tag_table'], $key, $this->record->getPk(), $this->record->tableName, $this->options['owner_id']
					);
					break;
				case 'update':
					/* update creation date and weight of tag */
					$this->record->db->query(
						'UPDATE %t SET created = now(), weight = %
						 WHERE name = % AND object_id = % AND object_table = % AND originated_from IS NULL AND owner_id = %',
						$this->options['tag_table'], $this->options['weight'], $key,
						$this->record->getPk(), $this->record->tableName, $this->options['owner_id']
					);
					break;
				case 'insert':
					/* insert new tag */
					$this->record->db->query(
						'INSERT INTO %t (name, weight, object_id, object_table, created_by) VALUES (%, %, %, %, %)',
						$this->options['tag_table'], $key, $this->options['weight'],
						$this->record->getPk(), $this->record->tableName, $this->options['owner_id']
					);
					break;
			}
		}
	}
}

?>