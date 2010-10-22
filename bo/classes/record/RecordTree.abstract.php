<?php

/*
	Class: RecordTree
	A nested set extension for the Record class.

	Nested sets allow storing hierarchical data in a relational database with the ability to efficiently
	make hierarchy-related queries.

	Any class that needs hierarchical abilities can be extended from this class instead of the normal
	Record class. This will implicitly add the following data properties:
	- A read/write *parent_id*
	- A read-only *left_id* and *right_id*
	- A read/write *tree_id*
	- A 1-to-1 *parent* of the same class
	- A 1-to-many *children* of the same class

	The columns that should be added to the table are the following:
	- tree_id: <any> not null
	- left_id: integer not null
	- right_id: integer not null
	- parent_id: integer null  (foreign key onto its own primary key)
	- An index (tree_id, left_id, right_id) (not unique)

	left_id and right_id should never need altering as these are handled by the extra methods introduced
	by this class. Parent and its id, while writable, should normally need no manual changes as these are
	also done by the class itself.

	tree_id is to allow the possibility of multiple independent trees in the same table. The class does
	not actually care what values are used, whoever uses the tree class is responsible for picking unique
	values themselves when creating the root node of a new tree.

	All tree manipulation routines have been written to query the current tree information from database
	before doing anything, so it is always safe to execute these functions as long as primary keys do not
	change.

	Adding/removing nodes:

	When creating a new root, tree_id must be set to some value, otherwise saving will fail.
	When adding a new node to an existing tree, use <$parent->addChild> to add the child node *before
	saving the child*. This will implicitly save the child as well.
	To move existing nodes around the tree, use <moveNode>.

	Deleting nodes is currently only allowed if they have no children.

	Node positions:
	<addChild> and <moveNode> have two optional parameters, $pos and $sibling. These are used to
	indicate where in the child list the node should be added.

	$pos can be one of the class constants LEFT or RIGHT. If given without a sibling, this indicates
	the node should be added to the extreme left or right of the children (i.e., whether it should
	be the first or last child). If a sibling node is given, it indicates whether the node should
	be added to the left or right of that sibling.

*/
abstract class RecordTree extends Record {
	const LEFT  = 0;
	const RIGHT = 1;

	public function __construct() {
		parent::__construct();
		$this->config['parent_id'] = array();
		$this->config['left_id']   = array('writability' => self::READONLY);
		$this->config['right_id']  = array('writability' => self::READONLY);
		$this->config['tree_id']   = array();

		$this->data['left_id']  = 1;
		$this->data['right_id'] = 2; // left / right ids for a single root
		$this->data['tree_id'] = 1; //default

		$this->hasOneConfig['parent'] = array(
			'class'   => get_class($this),
			'local'   => 'parent_id',
			'foreign' => $this->pkColumn
		);
		$this->hasManyConfig['children'] = array(
			'class'   => get_class($this),
			'local'   => $this->pkColumn,
			'foreign' => 'parent_id'
		);
	}

	/*
		Method: addChild
		Adds a new node as a child to the currently loaded node.

		Parameters:
		$child   - A not-yet saved RecordTree instance to be added.
		$pos     - One of <LEFT> or <RIGHT>, indicating the position where to add the node.
		$sibling - An optional RecordTree instance or primary key value of the new sibling.

		This method will add the given node to its own children at the requested position.
		The new node *should not be saved* yet, this method will do that itself - essentially,
		this method is a replacement for the save method for all child nodes.
	*/
	public function addChild(RecordTree $child, $pos = self::RIGHT, $sibling = null) {
		if (!$this->getPk()) {
			throw new Exception('Cannot add a child to an unsaved node.');
		}

		$this->db->query('BEGIN');

		$parentIds  = $this->db->query('SELECT left_id, right_id, tree_id FROM '.$this->tableName.' WHERE '.$this->pkColumn.' = %', $this->getPk())->fetchRow();
		if ($sibling) {
			if (is_object($sibling)) {
				$sibling = $sibling->getPk();
			}
			$siblingIds = $this->db->query('SELECT left_id, right_id, parent_id FROM '.$this->tableName.' WHERE '.$this->pkColumn.' = %', $sibling)->fetchRow();
		}
			// Make sure we have accurate left/right ids from the parent

		if (!$parentIds) {
			$this->db->query('ROLLBACK'); // End the transaction
			throw new Exception('The parent of the new node was deleted before adding the child.');
		}

		if ($sibling && $siblingIds['parent_id'] != $this->getPk()) {
			$this->db->query('ROLLBACK'); // End the transaction
			throw new Exception('Invalid sibling node given');
		}

		// Set our left/right ids
		if (!$sibling) {
			if ($pos == self::RIGHT) {
				$leftId = $parentIds['right_id'];
			} else {
				$leftId = $parentIds['left_id'] + 1;
			}
		} else {
			if ($pos == self::RIGHT) {
				$leftId = $siblingIds['right_id'] + 1;
			} else {
				$leftId = $siblingIds['left_id'];
			}
		}
		$rightId = $leftId+1;

		$this->db->query('UPDATE '.$this->tableName.' SET right_id = right_id + 2 WHERE right_id >= %1', $leftId);
		$this->db->query('UPDATE '.$this->tableName.' SET left_id = left_id + 2 WHERE left_id >= %1', $leftId);

		// Make sure the node has an ID
		if ($child->getPk() === false) {
			$child->data['left_id']  = $leftId;
			$child->data['right_id'] = $rightId;
			$child->data['tree_id']  = $parentIds['tree_id'];
			$child->data['parent_id'] = $this->getPk();
			$child->save();
		}

		$this->db->query('COMMIT'); // Save transaction & unlock tables.
		RecordCache::remove(get_class($this));
	}

	/*
		Method: preDelete
		Removes the node from the tree structure.
		If the delete event is not skipped, this method will update the tree structure to no longer contain the node.
		Actually deleting the node will still be done by the normal record->delete() method.
		Note that it is not allowed to delete a node that still has children!
	*/
	public function preDelete(RecordEvent $event) {
		if (!$event->skip) {
			$this->db->query('BEGIN');

			$nodeIds = $this->db->query('SELECT tree_id, left_id, right_id FROM '.$this->tableName.' WHERE '.$this->pkColumn.' = %', $this->getPk())->fetchRow();

			// Check if this node has children
			if ($nodeIds['right_id']-$nodeIds['left_id'] > 1) {
				$this->db->query('ROLLBACK');
				throw new Exception('Node specified for deletion has children.');
			}

			$this->db->query('UPDATE '.$this->tableName.' SET right_id = right_id - % WHERE tree_id = % AND right_id > %', $nodeIds['right_id'] - $nodeIds['left_id'] + 1, $nodeIds['tree_id'], $nodeIds['right_id']);
			$this->db->query('UPDATE '.$this->tableName.' SET left_id = left_id - %  WHERE tree_id = % AND left_id > %',  $nodeIds['right_id'] - $nodeIds['left_id'] + 1, $nodeIds['tree_id'], $nodeIds['right_id']);
			$this->db->query('COMMIT');
			RecordCache::remove(get_class($this));
		}
	}

	/*
		Method: moveNode
		Moves this node from one point in the tree to another.

		Parameters:
		$newParent - A RecordTree instance of primary key value of the new parent node.
		$pos       - One of <LEFT> or <RIGHT>, indicating the position where to add the node.
		$sibling   - An optional RecordTree instance or primary key value of the new sibling.

		This method will move the current node to become a child of the given parent at the requested position.
		The current node *should already be saved*, as well as the given parent.

		Nodes cannot be moved across trees, nor is it possible to move a node in such a way it would become
		a child (direct or indirect) of itself.
	*/
	public function moveNode($newParent, $pos = self::RIGHT, $sibling = null) {
		$this->db->query('BEGIN');

		if (is_object($newParent)) {
			$newParent = $newParent->getPk();
		}

		$childIds     = $this->db->query('SELECT tree_id, left_id, right_id FROM '.$this->tableName.' WHERE '.$this->pkColumn.' = %', $this->getPk())->fetchRow();
		$newParentIds = $this->db->query('SELECT tree_id, left_id, right_id, parent_id FROM '.$this->tableName.' WHERE '.$this->pkColumn.' = %', $newParent)->fetchRow();
		if ($sibling) {
			if (is_object($sibling)) {
				$sibling = $sibling->getPk();
			}
			$siblingIds = $this->db->query('SELECT tree_id, left_id, right_id, parent_id FROM '.$this->tableName.' WHERE '.$this->pkColumn.' = %', $sibling)->fetchRow();
		}

		if (!$childIds || !$newParentIds || ($sibling && !$siblingIds)) {
			$this->db->query('ROLLBACK');
			throw new Exception('Node to move, its new parent, or the given sibling do not exist.');
		}

		if ($childIds['tree_id'] != $newParentIds['tree_id']) {
			$this->db->query('ROLLBACK');
			throw new Exception('Attempting to move a node across roots');
		}

		if ($newParentIds['left_id'] >= $childIds['left_id'] && $newParentIds['right_id'] <= $childIds['right_id']) {
			$this->db->query('ROLLBACK');
			throw new Exception('Attempting to move a node to one of its children.');
		}

		if ($sibling && $siblingIds['parent_id'] != $newParent) {
			$this->db->query('ROLLBACK');
			throw new Exception('Invalid sibling node given');
		}

		$nodeWidth = $childIds['right_id']-$childIds['left_id'] + 1;

		if (!$sibling) {
			if ($pos == self::RIGHT) {
				$newLeftId = $newParentIds['right_id'];
			} else {
				$newLeftId = $newParentIds['left_id'] + 1;
			}
		} else {
			if ($pos == self::RIGHT) {
				$newLeftId = $siblingIds['right_id'] + 1;
			} else {
				$newLeftId = $siblingIds['left_id'];
			}
		}

		// Make space in the new parent node
		$this->db->query('UPDATE '.$this->tableName.' SET left_id = left_id + %  WHERE tree_id = % AND left_id >= %',  $nodeWidth, $childIds['tree_id'], $newLeftId);
		$this->db->query('UPDATE '.$this->tableName.' SET right_id = right_id + % WHERE tree_id = % AND right_id >= %', $nodeWidth, $childIds['tree_id'], $newLeftId);

		if ($childIds['left_id'] > $newLeftId) {
			// Our node to move was just moved. Update to reflect this
			$childIds['left_id']  += $nodeWidth;
			$childIds['right_id'] += $nodeWidth;
		}

		// Move the subtree to the parent node
		$this->db->query('
			UPDATE '.$this->tableName.'
			SET left_id = left_id + %, right_id = right_id + %1
			WHERE tree_id = % AND left_id >= % AND right_id <= %',
			$newLeftId-$childIds['left_id'],
			$childIds['tree_id'], $childIds['left_id'], $childIds['right_id']);

		// Remove the space left by the subtree at its original place
		$this->db->query('UPDATE '.$this->tableName.' SET left_id = left_id - %  WHERE tree_id = % AND left_id >= %',  $nodeWidth, $childIds['tree_id'], $childIds['left_id']);
		$this->db->query('UPDATE '.$this->tableName.' SET right_id = right_id - % WHERE tree_id = % AND right_id >= %', $nodeWidth, $childIds['tree_id'], $childIds['left_id']);

		// Update the parent reference
		$this->db->query('UPDATE '.$this->tableName.' SET parent_id = % WHERE '.$this->pkColumn.' = %', $newParent, $this->getPk());

		$this->db->query('COMMIT');
		RecordCache::remove(get_class($this));
	}

	/*
		Method: moveRight
		Swaps the node with its right sibling.
	*/
	public function moveRight() {
		if ($this->parent_id === null) {
			throw new Exception('Node has no parents.');
		}
		$siblingId = $this->db->query('SELECT id FROM '.$this->tableName.' WHERE tree_id = % AND left_id = %', $this->tree_id, $this->right_id + 1)->fetchCell();
		if (!$siblingId) {
			throw new Exception('Node has no right sibling.');
		}
		$this->moveNode($this->parent_id, self::RIGHT, $siblingId);

	}

	/*
		Method: moveLeft
		Swaps the node with its left sibling.
	*/
	public function moveLeft() {
		if ($this->parent_id === null) {
			throw new Exception('Node has no parents.');
		}
		$siblingId = $this->db->query('SELECT id FROM '.$this->tableName.' WHERE tree_id = % AND right_id = %', $this->tree_id, $this->left_id - 1)->fetchCell();
		if (!$siblingId) {
			throw new Exception('Node has no left sibling.');
		}
		$this->moveNode($this->parent_id, self::LEFT, $siblingId);
	}

	/*
		Method: loadTree
		Loads a node and its complete subtree.

		Parameters:
		$id         - The primary key value of the top node to load
		$withHasOne - See <Record::load> for this parameter.

		Returns:
		A single RecordTree instance for the node indicated by $id.

		This method loads an entire subtree with a single database query. The children property of
		each node loaded is initialized with all child node instances, so this can be used.
		Since all nodes in the subtree are loaded in one RecordCollection, loading associates of a
		single node will load the associates for all nodes in the entire subtree at once as well.

		WARNING: When using this function, the value of children will be a regular PHP array, *not*
		         a RecordCollection instance! This is because adding nodes to a new RecordCollection
		         will break its association with the previous collection.

		TODO: The parent hasOneData is not yet initialized.

		NOTE: For some reason, loading the associates of a subtree generates two queries - one for the
		      first queried node, then for the entire tree. Isn't the first query superfluous?
	*/
	public function loadTree($id, $withHasOne = self::LOAD_DEFAULT) {
		$nodeClass = get_class($this);
		// Check if this is a leaf node

		$ids = $this->db->query('SELECT left_id, right_id, tree_id FROM '.$this->tableName.' WHERE '.$this->pkColumn.' = %', $id)->fetchRow();
		if (!$ids) {
			throw new RecordNotFoundException('The record with the primary key "'.$id.'" could not be found');
		}
		$nodes = $this->select()->withHasOne($withHasOne)
			->where('left_id >= % AND right_id <= % AND tree_id = %', $ids['left_id'], $ids['right_id'], $ids['tree_id'])
			->order('left_id ASC')
			->get();
		$root = $nodes->first();
		// Create the nodes and assign them to the right children
		$nodeStack = array();
		foreach ($nodes as $node) {
			// Remove nodes not in the current subtree
			while (end($nodeStack) !== false && end($nodeStack)->right_id-1 < $node->right_id) {
				array_pop($nodeStack);
			}

			$node->hasManyData['children'] = array();

			// Add this node as child
			if (end($nodeStack) !== false)
				end($nodeStack)->hasManyData['children'][$node->getPk()] = $node;

			// Add node to the stack
			$nodeStack[] = $node;
		}

		return $root;
	}


	/**
	 * Builds select query that will limit the search to parent and descendant nodes only.
	 *
	 * @param integer $id parent node id
	 * @return RecordQuery
	 */
	public function selectDescendants($id = null, $withHasOne = self::LOAD_DEFAULT) {
		$id = $id === null? $this->id: $id;
		if($id === null || $id === false) {
			throw new RecordNotFoundException('This record doesn\'t exists!');
		}

		return $this->select()->withHasOne($withHasOne)
			->join('INNER JOIN '.$this->tableName.' sar ON t1.left_id >= sar.left_id AND t1.right_id <= sar.right_id AND t1.tree_id = sar.tree_id AND sar.id = %', $id)
			->order('left_id ASC');
	}

	/*
		Method: getPath
		Returns the path from the root of the tree to the current node.

		Returns:
		A RecordCollection of tree nodes starting at the root of the tree, and containing all nodes
		needed to reach the currently loaded node.

		This method uses a single query to obtain the nodes in the path.
	*/
	public function getPath() {
		return $this->select()
			->where('left_id <= % AND right_id >= % AND tree_id = %', $this->left_id, $this->right_id, $this->tree_id)
			->order('left_id')
			->get();
	}

	/*
		Method: getLevels
		Returns the levels of the nodes

		Returns:
		Returns an associative array with as keys the itemid and as value the level it's on
	*/
	public function getLevels() {
		return $this->db->query('SELECT count(p.id), g.id
								FROM %1t g
								JOIN %1t p ON p.left_id < g.left_id AND p.right_id > g.right_id
								GROUP BY g.id', $this->tableName)->fetchAllCells('id');
	}
}

?>