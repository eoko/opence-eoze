<?php
/**
 * @author Éric Ortéga <eric@mail.com>
 * @copyright Copyright (c) 2010, Éric Ortéga
 * @license http://www.planysphere.fr/licenses/psopence.txt
 * @package PS-ORM-1
 * @subpackage Query
 */

class ResultSet implements IteratorAggregate, ArrayAccess {

	/** @var PDOStatement */
	protected $pdoStatement;
	protected $results;

	/**
	 * Fetch all row at once.
	 */
	const FETCH_ALL = 1;
	/**
	 * Fetch rows one by one (currently not implemented)
	 */
	const FETCH_ROW = 2;

	public function __construct(PDOStatement $pdoStatement, $fetchMode = ResultSet::FETCH_ROW) {
		$this->pdoStatement = $pdoStatement;
		$this->results = $this->pdoStatement->fetchAll(PDO::FETCH_ASSOC);
	}

	// Iterator
	public function getIterator() {
		return new ArrayIterator($this->results);
	}
	
	// ArrayAccess
	public function offsetExists($offset) {
		return array_key_exists($offset, $this->results);
	}
	public function offsetGet($offset) {
		return $this->results[$offset];
	}
	public function offsetSet($offset, $value) {
		$this->results[$offset] = $value;
	}
	public function offsetUnset($offset) {
		unset($this->results[$offset]);
	}

}

class GenericResultSet extends ResultSet {

	/** @var ModelTable */
	private $table;

	public function __construct(ModelTable $table, PDOStatement $pdoStatement) {
		parent::__construct($pdoStatement);
		$this->table = $table;
	}
}