<?php
/**
 * @author Éric Ortéga <eric@mail.com>
 * @copyright Copyright (c) 2010, Éric Ortéga
 * @license http://www.planysphere.fr/licenses/psopence.txt
 * @package PS-ORM-1
 * @subpackage Query
 */

use eoko\database\ConnectionManager;

/**
 * Utility class to submit queries to the database
 *
 * Executing a query with this class is a 3-steps process:<ol>
 * <li>Create the Query object
 * <li>Specify the query options, depending on the type of Query
 * <li>Call the general {@link Query::execute() execute()} method or one of the
 * operation-specific executeXXX method (eg. executeSelect())
 * </ol>
 *
 * Two method are available to create the Query object. Either use the static
 * {@link Query::create() create()} method: <code>Query::create()</code> or
 * <code>Query::create(TABLE_NAME)</code>, or call the
 * {@link ModelTable::createQuery()} method of a given {@link ModelTable}.
 *
 * The create() method, as well as all other methods of this class except the
 * execute ones returns the Query object instance, so that operations and
 * options can be chained in this elegant way:<code>
 * Query::create()<br>
 * &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;->select()<br>
 * &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;->from(...)<br>
 * &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;->where(...)->andWhere(...)<br>
 * &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;->execute()
 * </code>
 *
 * One of the 4 CRUD operations must be specified by the following methods
 * (if the operation doesn't need to sepcify any option, then the operation
 * can be specified simply by calling the corresponding executeXXX method,
 * where XXX is the name of the operation):<ul>
 * <li>Create: {@link Query::insert()}
 * <li>Read: {@link Query::select()} or {@link Query::selectFirst()}
 * <li>Update: {@link Query::set()}
 * <li>Delete: {@link Query::delete()}
 * </ul>
 *
 * Finally, use one of the execute() method to execute the query and retrieve
 * the results. There is one generic {@link Query::execute() execute()} method
 * that determines the type of operation from the previously called methods,
 * and operation-specific executeOperation() methods, where Operation is the
 * name of the operation: {@link Query::executeInsert() executeInsert()},
 * {@link Query::executeSelect() executeSelect()} and
 * {@link Query::executeSelectFirst() executeSelectFirst()},
 * {@link Query::executeUpdate() executeUpdate()}, and
 * {@link Query::executeDelete() executeDelete()}.
 */
class Query {

	private $db = null;

	private $table = null;
	/** @var myModelTable */
	protected $modelTable = null;
	/** @var string */
	private $dbTable = null;
	protected $tableAlias = null;

	protected $select = null;
	private $set = array();
	/** @var QueryWhere */
	private $where = null;

	private $limitStart = false, $limit = false;
	private $order = false;
	private $defaultOrder = false;

	protected $additionnalInserts = null;

	protected $joins = null;
	protected $joinTakenAliases = array();

	protected $groupBy = array();

	/** @var QueryErrorHandler */
	protected $errorHandler;

	/** @var array */
	public $context = array();

	/** @var string */
	private $sql = null;
	/** @var array */
	protected $bindings = array();

	private $sqlVarId = 0;

	/**
	 * Allowed dir (as in ORDER BY ... *DIR*) values -- used for input protection.
	 * Trying to order by another dir value will throw an IllegalOffset error.
	 * @todo check that this error is actually blocking in production settings...
	 * @var array
	 */
	protected static $dirValues = array(
		'asc' => 'ASC', 'ASC' => 'ASC',
		'desc' => 'desc', 'DESC' => 'DESC'
	);


//REM	protected static $INSERT = 'insert';
//	protected static $INSERT_OR_UPDATE = 'insert_or_update';
//	protected static $UPDATE = 'update';
//	protected static $SELECT = 'select';
//	protected static $SELECT_FIRST = 'selectOne';
//	protected static $DELETE = 'delete';
//	protected static $COUNT = 'count';

	const INSERT			= 1;
	const INSERT_OR_UPDATE	= 2;
	const UPDATE			= 3;
	const SELECT			= 4;
	const SELECT_FIRST		= 5;
	const DELETE			= 6;
	const COUNT				= 7;

	protected $action = null;

	protected function __construct($table, array $context = array()) {

		$this->errorHandler = new QueryErrorHandler();

		$this->context = $context;

		if ($table instanceof ModelTable) {
			$table->attach($this->modelTable);
			$this->dbTable = $table->getDBTableName();
		} else {
			$this->dbTable = $table;
		}
	}

	/**
	 * Create a new Query object. The database table can optionaly be specified
	 * here.
	 * @param String $table
	 * @return Query
	 */
	public static function create(ModelTable $table = null, array $context = array()) {
		return new Query($table, $context);
   	}

	/**
	 * Reset the previously set Select option.
	 *
	 * However, this method doesn't clear the operation from the request, that
	 * is the Query will be considered a Select (READ) operation if the Query
	 * is executed now -- moreover,
	 *
	 * @return Query
	 */
	public function resetSelect() {
		$this->select = null;
		return $this;
	}

	public function getFreeVariableName() {
		$this->sqlVarId++;
		return "@autovar$this->sqlVarId";
	}

	/**
	 * Get the next available unique alias for the given table. The aliases are
	 * constructed by appending incrementing numbers to the name of the tables.
	 * If the required table is also selected in the FROM clause, the aliases
	 * will start at 2, else they will start at nothing, then 2, etc.
	 *
	 * <b>Important</b> it is forbiden to change the FROM clause after one alias
	 * has been generated (IllegalStateException would be thrown).
	 *
	 * @param string $dbTable
	 * @return string
	 */
	public function getNextJoinAlias($dbTable) {
		if (array_key_exists($dbTable, $this->joinTakenAliases)) {
			$i = ++$this->joinTakenAliases[$dbTable];
			return $dbTable . $i;
		} else {
			if ($dbTable === $this->dbTable) {
				$this->joinTakenAliases[$dbTable] = 2;
				return $dbTable . '2';
			} else {
				$this->joinTakenAliases[$dbTable] = 1;
				return $dbTable;
			}
		}
	}

	public static function selectClause($field, $alias = false, $table = false) {
		$r = array();
		$r['name'] = $field;
		if ($alias !== false) $r['alias'] = $alias;
		if ($table !== false) $r['table'] = $table;
		return $r;
	}

	public function createFormattedSelect($alias, $format, $table = null) {
		return new QueryFormattedSelect($this, $alias, $format, $table);
	}

	/**
	 * @return QueryWhere
	 */
	public function createWhere($condition = null, $inputs = null) {
		$r = new QueryWhere($this, $condition, $inputs);
		return $r;
	}

	protected function hasJoins() {
		return $this->joins !== null;
	}

	protected function mergeJoinSelect(&$parts = array()) {
		if ($parts === null) $parts = array();
		if ($this->joins !== null) {
			foreach ($this->joins as $join) {
				if (($s = $join->buildSelect($this->bindings)))
					$parts[] = $s;
			}
		}
		return $parts;
	}
	
	protected function buildJoinsClauses() {
		if ($this->joins !== null) {
//			$selects = array();
			$joins = array();
			foreach ($this->joins as $join) {
//				$join instanceof QueryJoin;
				$joins[] = $join->buildSql($this->bindings);
			}
			return ' ' . implode(' ', $joins);
		}
		return null;
	}

	/**
	 * Add one or more fields to the select query.
	 *
	 * The arguments can be passed either as an array, or multiple parameters
	 * of a mixing of the following:<ul>
	 * <li>a simple string containing the name of a field
	 * <li>a QuerySelectElement object
	 * <li>an array containing at least a key 'name', and optionnaly a key 'alias'
	 * and/or a key 'table'
	 * </ul>
	 *
	 * For each field element, if no table is specified, the table of the
	 * last from will be used; and if no alias is specified, the name of the
	 * field will be used.
	 *
	 * @param <mixed> $_,... If set to null, then that field will reset the
	 * select clause when processed.
	 * @return Query
	 */
	public function select($_ = null) {

		$this->action = self::SELECT;

		if ($_ == null) {
			$this->select = null;
			return $this;
		} else if (func_num_args() > 1) {
			$fields = func_get_args();
		} else {
			if (is_array($_)) $fields = $_;
			else $fields = array($_);
		}
		
		if ($this->select === null) $this->select = array();

		foreach ($fields as $field) {
			if ($field instanceof QuerySelect) {
				$this->select[] = $field;
			} else if (is_array($field)) {
				$this->select[] = new QuerySelect(
					$field['name'],
					isset($field['alias']) ? $field['alias'] : null,
					isset($field['table']) ? $field['table'] : $this->dbTable
				);
			} else {
				$this->select[] = new QuerySelect($field, null, $this->dbTable);
			}
		}

		return $this;
	}

	/**
	 *
	 * @param mixed $___
	 * @return Query
	 */
	public function selectFirst($___ = null) {
		if ($___ !== null) {
			$r = $this->select($___);
		}
		$this->action = self::SELECT_FIRST;
		return $this;
	}

	private $selectDistinct = true;

	/**
	 * @return Query
	 */
	public function setSelectDistinct($flag) {
		$this->selectDistinct = (bool) $flag;
		return $this;
	}

	private function buildSelect() {

		$this->bindings = array();
		$table = "`$this->dbTable`";
		$parts = array();

		$joinParts = $this->mergeJoinSelect();

		if ($this->select === '*' || (count($joinParts) == 0 && $this->select === null)) {
			$parts[] = "$table.*";
		} else if ($this->select !== null) {
			$defaultTable = $this->hasJoins() ? false : $this->table;
			foreach ($this->select as $field) {
				$parts[] = $field->buildSql($defaultTable, $this->bindings);
			}
		}

		$parts = array_merge($parts, $joinParts);

//		$this->sql = 'SELECT ' . 'DISTINCT ' // TODO rx HACK handle distinct ...
		$this->sql = 'SELECT ' . ($this->selectDistinct ? 'DISTINCT ' : null) // TODO rx HACK handle distinct ...
				. implode(', ', $parts)
				. $this->buildFrom()
				. $this->buildJoinsClauses()
				. $this->buildWhere()
				. $this->buildGroupBy()
				. $this->buildOrder()
				. $this->buildLimit()
				. ';';
	}

	/**
	 *
	 * @param mixed $col		the name of the column to set, or an array
	 * containing multiple set rules in the form $colName => $value.
	 * @param mixed $value	if the first param is a single string, then
	 * this param must be the value to which the col must be set, else it is
	 * ignored
	 * @return Query
	 */
	public function set($col, $value = null) {

		if ($this->action == null) $this->action = self::UPDATE;

		if (is_array($col)) {
			foreach ($col as $col => $val) {
				$this->set[$col] = $val;
			}
		} else {
//			if (func_num_args() != 2) {
//				throw new IllegalArgumentException();
//			}
			$this->set[$col] = $value;
		}

		return $this;
	}

	/**
	 * @param array $values
	 * @return Query
	 */
	public function insert($values = null) {
		$this->action = self::INSERT;

		if ($values !== null) return $this->set($values);

		return $this;
	}

	public function insertMultiple($values) {
		// TODO rx implement that correctly ...
		$this->insert(array_shift($values));
		foreach ($values as $value) {
			$this->andInsert($value);
		}
		return $this;
	}

	public function andInsert($values) {
		if ($this->additionnalInserts === null) $this->additionnalInserts = array();
		$this->additionnalInserts[] = $values;
	}

	public static function format($format, QueryAliasable $aliasable) {

		$regex = '/%([^%]+)%/';

		preg_match_all($regex, $format, $matches);

		$fields = $matches[1];

		$glueParts = preg_split($regex, $format);

//		$qTable = $table !== null ? "`$table`." : null;

		$parts = array();
		for ($i=0, $l=count($fields); $i<$l; $i++) {
			$glue = str_replace("'", "\\'", $glueParts[$i]);
//			array_push($parts, "'$glue'", "$qTable`$fields[$i]`");
			array_push($parts, "'$glue'", $aliasable->getQualifiedName($fields[$i]));
		}
		if (isset($glueParts[$i])) {
			$parts[] = "'" . str_replace("'", "\\'", $glueParts[$i]) . "'";
		}

		return 'CONVERT(CONCAT(' . implode(', ', $parts) . ") USING utf8)";
//		return 'CONCAT(' . implode(', ', $parts) . ")";
	}

	public static function SqlFunction($fn) {
		return new SqlFunction($fn);
	}

	/**
	 * @return PDOStatement
	 */
	public function delete() {
		$this->action = self::DELETE;
		return $this;
	}

	private function buildDelete() {
		if (!$this->hasWhere()) throw new IllegalStateException();
		$this->bindings = array();
		$this->sql = 'DELETE'
				. $this->buildFrom()
				. $this->buildWhere()
				. $this->buildGroupBy()
				. $this->buildOrder()
				. $this->buildLimit()
				. ';';
	}

	protected function buildUpdateClause() {
		$parts = array();
		foreach ($this->set as $col => $val) {
			
			if ($val instanceof SqlVar) {
				$val->buildSql(false, $this->bindings);

				$v = $val->buildSql(false, $this->bindings);
				if ($v instanceof SqlVar) {
					$v = $v->buildSql(false, $this->bindings);
				}

				$parts[] = $col instanceof SqlVar ? $col->buildSql(false, $this->bindings)
					: $this->getQualifiedName($col) . " = $v";
			} else {

				$parts[] = $col instanceof SqlVar ? $col->buildSql(false, $this->bindings)
					: $this->getQualifiedName($col) . ' = ?';

				if ($val instanceof SqlFunction) {
					$this->bindings[] = $val->getString();
				} else {
					$this->bindings[] = $val;
				}
			}
       	}

		return 'UPDATE ' . $this->buildTable(true) 
				. ($this->hasJoins() ? $this->buildJoinsClauses() : null)
				. ' SET '
				. implode(', ', $parts);
	}

	private function buildUpdate() {
		$this->sql = $this->buildUpdateClause()
				. $this->buildWhere()
				. ';';
	}

	protected function buildInsertOrUpdate() {
		$this->buildInsert(null);
		$this->sql .= ' ON DUPLICATE KEY '
				. $this->buildUpdateClause()
				. ';';
	}

	private function buildInsert($colon = ';') {

		$n = 1;
		$this->bindings = array();
		$values = array();
		$fields = array();

		if (count($this->set) > 0) {

			foreach ($this->set as $col => $val) {
				$fields[] = self::quoteName($col);

				if (!$val instanceof SqlFunction) {
					$values[] = '?';
					$this->bindings[] = $val;
				} else {
					$values[] = $val->getString();
				}
			}
			$this->sql = substr($this->sql, 0, -2);
		}
		
		$fields = implode(', ', $fields);
		$values = implode(',', $values);
		$table = $this->buildTable();

		if ($this->additionnalInserts !== null) {
			$n += count($this->additionnalInserts);
			$values = array("($values)");
			foreach ($this->additionnalInserts as $v) {
				$valString = array();
				foreach ($v as $col => $val) {
					if (!$val instanceof SqlFunction) {
						$valString[] = '?';
						$this->bindings[] = $val;
					} else {
						$valString[] = $val->getString();
					}
				}
				$values[] = '(' . implode(',', $valString) . ')';
//				$values[] = '(' . implode(', ', $v) . ')';
			}
			$values = implode(',', $values);
		} else {
			$values = "($values)";
		}

		$this->sql = "INSERT INTO $table ($fields) VALUES $values$colon";

		return $n;
	}

	/**
	 * Set the WHERE condition of the Query, overriding any previously set
	 * WHERE clause.
	 * @return Query
	 */
	public function where($condition, $inputs = null) {
		if (func_num_args() > 2) $inputs = array_splice(func_get_args(), 1);
		$this->where = $this->createWhere($condition, $inputs);
//		if ($condition instanceof QueryWhere)
//			$this->where = $condition;
//		else
//			$this->where = QueryWhere::create()->where($condition, $inputs);
		return $this;
	}

	/**
	 *
	 * @return Query
	 * @see where
	 */
	public function andWhere($condition, $inputs = null) {
		if (func_num_args() > 2) $inputs = array_splice(func_get_args(), 1);
		if (!$this->hasWhere()) {
			return $this->where($condition, $inputs);
		}
		$this->where->andWhere($condition, $inputs);
		return $this;
	}

	/**
	 *
	 * @return Query
	 */
	public function orWhere($condition, $inputs = null) {
		if (func_num_args() > 2) $inputs = array_splice(func_get_args(), 1);
		if (!$this->hasWhere()) return $this->where($condition, $inputs);
		$this->where->orWhere($condition, $inputs);
		return $this;
	}

	/**
	 *
	 * @return Query
	 */
	public function whereIn($field, $values) {
		$this->where = $this->createWhere();
		if (func_num_args() > 2) $values = array_splice(func_get_args(), 1);
		$this->where->whereIn($field, $values);
		return $this;
	}

	/**
	 * @return Query
	 */
	public function whereNotIn($field, $values) {
		$this->where = $this->createWhere();
		if (func_num_args() > 2) $values = array_splice(func_get_args(), 1);
		$this->where->whereNotIn($field, $values);
		return $this;
	}

	/**
	 * @return Query
	 */
	public function andWhereIn($field, $values) {
		if (func_num_args() > 2) $values = array_splice(func_get_args(), 1);
		if (!$this->hasWhere()) return $this->whereIn($field, $values);
		$this->where->andWhereIn($field, $values);
		return $this;
	}

	/**
	 * @return Query
	 */
	public function andWhereNotIn($field, $values) {
		if (func_num_args() > 2) $values = array_splice(func_get_args(), 1);
		if (!$this->hasWhere()) return $this->whereNotIn($field, $values);
		$this->where->andWhereNotIn($field, $values);
		return $this;
	}

	/**
	 * @return Query
	 */
	public function orWhereIn($field, $values) {
		if (func_num_args() > 2) $values = array_splice(func_get_args(), 1);
		if (!$this->hasWhere()) return $this->whereIn($field, $values);
		$this->where->orWhereIn($field, $values);
		return $this;
	}

	/**
	 * @return Query
	 */
	public function orWhereNotIn($field, $values) {
		if (func_num_args() > 2) $values = array_splice(func_get_args(), 1);
		if (!$this->hasWhere()) return $this->whereNotIn($field, $values);
		$this->where->orWhereNotIn($field, $values);
		return $this;
	}

	/**
	 * @return Bool
	 */
	private function hasWhere() {
		return $this->where !== null;
	}

	private function buildWhere() {

		if ($this->hasWhere()) {
			$s = ' WHERE ' . $this->where->buildSql($this->bindings);
			return $s;
		} else {
			return '';
		}
	}

	public function setTableAlias($alias) {
		$this->tableAlias = $alias;
	}

	private function buildFrom($allowOnlyOne = false) {
		return ' FROM ' . $this->buildTable($allowOnlyOne)
				. ($this->tableAlias !== null ? " AS `$this->tableAlias`" : null);
	}

	/**
	 *
	 * @param Bool $allowOnlyOne
	 * @return String
	 */
	private function buildTable($allowOnlyOne = false) {
		if ($this->db !== null) {
			return "`$this->db`.`$this->dbTable`";
		} else {
			return "`$this->dbTable`";
		}
	}

	/**
	 * @return Query
	 */
	public function onDatabase($db) {
		$this->db = $db;
		return $this;
	}

	/**
	 * @return Query
	 */
	public function limit($limit, $start = false) {
		if ($start === null) $start = 0;
		$this->limitStart = (int) $start;
		$this->limit = (int) $limit;
		return $this;
	}

	protected function hasGroupBy() {
		return count($this->groupBy) > 0;
	}

	public function groupBy($field) {
		$this->groupBy[] = $field;
	}

	protected function buildGroupBy() {
		if (count($this->groupBy) > 0) {
			return ' GROUP BY ' . implode(', ', $this->groupBy);
		} else {
			return null;
		}
	}

	private function hasLimit() {
		return $this->limit !== false;
	}

	private function buildLimit() {
		if ($this->hasLimit()) {
			if ($this->limitStart !== false) {
				return ' LIMIT ' . $this->limitStart . ',' . $this->limit;
			} else {
				return ' LIMIT ' . $this->limit;
			}
		} else {
			return '';
		}
	}

	/**
	 * Set the sort method of the query, overwritting previously set order, if
	 * any.
	 * @return Query
	 */
	public function orderBy($order, $dir = 'ASC') {
		$this->order = array();
		return $this->thenOrderBy($order, $dir);
	}

	public function defaultOrderBy($order, $dir = 'ASC') {
		$this->defaultOrder = array();
		return $this->thenOrderBy($order, $dir);
	}

	/**
	 * @return Query
	 */
	public function defaultThenOrderBy($field, $dir = 'ASC') {
		return $this->addThenOrderBy($this->defaultOrder, $field, $dir);
	}

	public static function protectDir(&$dir) {
		return self::$dirValues[$dir];
	}

	/**
	 * @return Query
	 */
	public function thenOrderBy($field, $dir = 'ASC') {
		return $this->addThenOrderBy($this->order, $field, $dir);
	}
	
	private function addThenOrderBy(&$order, $field, $dir = 'ASC') {
		if ($field == null || $field == '') {
			throw new Exception('Illegal Argument Exception: $order cannot be empty');
		}

		if (!is_array($order)) {
			$order = array();
		}

		if (is_array($field)) {
			foreach ($field as $o => $dir) $this->thenOrderBy($o, $dir);
		} else {
			if ($dir === '') $dir = 'ASC';
			else $dir = self::$dirValues[$dir]; // protect from injection

			if ($field instanceof SqlVar) {
				Logger::warn('The next line is most probably wrong and causing problem with bindings');
				$this->order[] = $field->buildSql(false, $this->bindings);
			} else {
				$order[] = $this->getOrderFieldAlias($field) . " $dir";
			}

//			// Clear previous to add new order at the end of the list
//			unset ($this->order[$order]);
		}

		return $this;
	}

	private function buildOrder() {
		if (!$order = ($this->order !== false ? $this->order : ($this->defaultOrder !== false ? $this->defaultOrder : false))) {
			return null;
		} else {
			return ' ORDER BY ' . implode(', ', $order);
		}
	}

	public static function quoteName($name) {
		if ($name[0] !== '`') return '`' . $name . '`';
		else return $name;
	}

	/**
	 *
	 * @return PDOStatement
	 */
	private function executeSql(&$pdo = null) {

		$pdo = ConnectionManager::get();
		$pdoStatement = $pdo->prepare($this->sql);

		$this->getLogger()->debug("Executing query:\n{}", $this);

		if (!$pdoStatement->execute($this->bindings)) {
			$errorInfo = $pdoStatement->errorInfo();
//			throw new SystemException($errorInfo[2]);
			$this->errorHandler->process($this, $errorInfo);
		}

		return $pdoStatement;
	}

	private static function getLogger() {
		return Logger::getLogger('Query');
	}

	/**
	 * @return int number of affected rows.
	 */
	public function executeUpdate() {
		$this->action = self::UPDATE;
		$this->buildUpdate();
		return $this->executeSql()->rowCount();
	}

	/**
	 * @return int number of affected rows.
	 */
	public function executeDelete() {
		$this->action = self::DELETE;
		$this->buildDelete();
		return $this->executeSql()->rowCount();
	}

	/**
	 * @return int the count
	 */
	public function executeCount($distinct = false) {
		$this->action = self::COUNT;
		$this->buildCount($distinct);
		return $this->executeSql()->fetchColumn();
	}

	/**
	 * @return mixed primary key value of the newly inserted row, or null if
	 * the insert failed.
	 */
	public function executeInsert() {
		$this->action = self::INSERT;
		$n = $this->buildInsert();
		if ($this->executeSql($pdo)->rowCount() == $n) {
			if ($n == 1) {
				return $pdo->lastInsertId();
			} else {
				return $n;
			}
		} else {
			return null;
		}
	}

	/**
	 * @return <mixed> primary key value of the last inserted row, or NULL if
	 * the insert failed. <b>Important</b>: if an id is returned, it is NOT
	 * garanteed to be the one of a newly created row -- it must be checked
	 * against a previously known primary key to determine that...
	 */
	public function executeInsertOrUpdate() {
		$this->action = self::INSERT_OR_UPDATE;
		$this->buildInsertOrUpdate();
		if ($this->executeSql($pdo)->rowCount() == 1) {
			return $pdo->lastInsertId();
		} else {
			return null;
		}
	}

	/**
	 * @return Array
	 */
	public function executeSelectFirst() {
		self::getLogger()->debug('Executing select query');

		$this->action = self::SELECT_FIRST;

		$this->limit(1);

		$this->buildSelect();

		$results = $this->executeSql()->fetchAll(PDO::FETCH_ASSOC);

		if (count($results) < 1) return null;

		return $results[0];
	}

	/**
	 * Execute the select query and returns a raw PDOStatement
	 * @return PDOStatement
	 */
	public function executeSelectRaw() {

		self::getLogger()->debug('Executing select query');

		$this->action = self::SELECT;
		$this->buildSelect();

		return $this->executeSql();
	}

	/**
	 *
	 * @return PDOStatement
	 */
	public function reExecuteSelectRaw() {
		return $this->executeSql();
	}

	public function executeSelectColumn($colIndex = 0) {
		self::getLogger()->debug('Executing select query');
		
		$this->action = self::SELECT;
		$this->buildSelect();

		return $this->executeSql()->fetchAll(PDO::FETCH_COLUMN, $colIndex);
	}

	public function executeSelectValue() {
		$col = $this->executeSelectColumn(0);
		return $col[0];
	}
	
	/**
	 *
	 * @return array
	 */
	public function executeSelect() {

		self::getLogger()->debug('Executing select query');
		
		$this->action = self::SELECT;
		$this->buildSelect();

//		return new ResultSet($this->executeSql());
		return $this->executeSql()->fetchAll(PDO::FETCH_ASSOC);
	}

	public function count() {
		$this->action = self::COUNT;
		return $this;
	}

	protected function buildCount($distinct = false) {

		$this->bindings = array();

		if ($this->hasWhere()) {
		}

		$field = $distinct ? $this->buildCountField() : '*';

		$this->sql = "SELECT COUNT($field)"
				. $this->buildFrom()
				. $this->buildJoinsClauses()
				. $this->buildWhere()

//				. $this->buildOrder()
//				. $this->buildLimit()
				. ';';
	}

	protected function buildCountField() {
		throw new UnsupportedOperationException('Query::buildCountField()');
	}

	public function exists() {
		return $this->executeCount() > 0;
	}

	/**
	 * Execute the query
	 *
	 * The type of operation is determined by the previously called methods.
	 * Each of the following methods will set the operation: {@link insert()},
	 * {@link select()} (or {@link selectFirst()}, {@link update()}, and
	 * {@link delete()}. If none of this method is called, but the {@link set()}
	 * method has been used, then the operation will be considered an Update;
	 * if set() hasn't been called either, then this method will be stuck and
	 * throw an IllegalStateException.
	 *
	 * @return mixed See {@link executeInsert()}, {@link executeSelect()},
	 * {@link execute()}, etc.
	 *
	 * @throws IllegalStateException if no previously called method has made it
	 * possible to determines the operation to execute
	 */
	public function execute() {
		switch ($this->action) {
			case self::SELECT: return $this->executeSelect();
			case self::SELECT_FIRST: return $this->executeSelectFirst();
			case self::UPDATE: return $this->executeUpdate();
			case self::INSERT: return $this->executeInsert();
			case self::DELETE: return $this->executeDelete();
			case self::COUNT: return $this->executeCount();
			default: throw new IllegalStateException("No action specified");
		}
	}

	public function save() {
		$this->execute();
	}

	/**
	 * Internally build the database query corresponding to the currently set
	 * options of the Query. This method uses the same rules as the 
	 * {@link execute()} method to determine the current operation.
	 *
	 * This method doesn't return anything. {@internal The SQL string is just
	 * built and stored in the $sql variable member, as an internal representation
	 * -- that is, this is <b>NOT</b> valid SQL and will most probably raise
	 * errors if it is submitted directly to the DBMS.}}
	 */
	private function build() {
		switch ($this->action) {
			case self::SELECT_FIRST:
			case self::SELECT: return $this->buildSelect();
			case self::UPDATE: return $this->buildUpdate();
			case self::INSERT: return $this->buildInsert();
			case self::DELETE: return $this->buildDelete();
			case self::COUNT: return $this->buildCount();
			default: throw new IllegalStateException("Illegal State: Unreachable code");
		}
	}

	public function buildSql($defaultTable, &$bindings) {
		$this->build();
		if (is_array($bindings) && count($this->bindings) > 0) {
			$bindings = array_merge($bindings, $this->bindings);
		}
		return $this->sql;
	}

	/**
	 * Get a human-readable representation of this Query. This can be useful
	 * for debugging purpose.
	 * @return String
	 */
	public function __toString() {
		$s = 'Query ';
//		if (count($this->table) == 0) {
//			$s .= '{ NULL }';
//		} else {
//			$s .= count($this->table) == 1 ? 'on table ' : 'on tables ';
			$s .= "on table $this->dbTable";
			$s .= $this->buildTable();

			if ($this->action === null) {
				$s .= ' { NULL }';
			} else {
				$s .= ' {' . PHP_EOL;

				$clean = false;
				if ($this->sql === null) {
					$clean = true;
					$this->build();
				}

				$s .= "\t" . '[sql] => ' . $this->sql . PHP_EOL;
				$s .= "\t" . '[bindings] => ' . implode(', ', $this->bindings) . PHP_EOL;
				$s .= '}';

				if ($clean) {
					$this->sql = null;
					$this->bindings = array();
				}
			}
//		}
		return $s;
	}

	public function getSql($trimColon = false, $prefix = null, $suffix = null) {
		$clean = false;
		if ($this->sql === null) {
			$clean = true;
			$this->build();
		}

		$sql = $this->sql;
		if ($trimColon && substr($sql, -1) === ';') $sql = substr($sql, 0, -1);
		$sql = "$prefix$sql$suffix";

		if (is_array($this->bindings) && count($this->bindings) > 0) {
			$sql = new SqlBindingVariable(
				$sql,
				$this->bindings
			);
		}

		if ($clean) {
			$this->sql = null;
			$this->bindings = array();
		}

		return $sql;
	}

	public function getSqlString() {
		$clean = false;
		if ($this->sql === null) {
			$clean = true;
			$this->build();
		}

		$sql = $this->sql;

		foreach ($this->bindings as $b) {
			$sql = preg_replace('/\?/', "'$b'", $sql, 1);
		}

		if ($clean) {
			$this->sql = null;
			$this->bindings = array();
		}
		
		return $sql;
	}

	/**
	 *
	 * @param string $sql
	 * @param QueryErrorHandler $errorHandler
	 * @return PDOStatement
	 */
	public static function executeQuery($sql, $errorHandler = null) {
		
		Logger::get('Query')->debug('Executing raw query: {}', $sql);
		
		$sth = ConnectionManager::get()->prepare($sql);
		
		if ($sth->execute()) {
			return $sth;
		} else {
			if ($errorHandler === null) {
				QueryErrorHandler::getInstance()->process(null, $sth->errorInfo());
			} else {
				if ($errorHandler === false) {
					return false;
				} else if (is_callable($errorHandler)) {
					call_user_func($errorHandler, $this, $sth->errorInfo());
				} else if ($errorHandler instanceof QueryErrorHandler) {
					$errorHandler->process($this, $sth->errorInfo());
				} else {
					throw new IllegalArgumentException('$errorHandler => ' . $errorHandler);
				}
			}
		}
	}

	public static function ageFunction($dateField, $alias = 'age', $quoteField = false) {
		if ($quoteField) $dateField = "`$dateField`";

		return new SqlVariable(
<<<SQL
CONVERT(CONCAT(IF((@years := (DATE_FORMAT(NOW(), '%Y') - DATE_FORMAT($dateField, '%Y')
- (@postBD := (DATE_FORMAT(NOW(), '00-%m-%d') < DATE_FORMAT($dateField, '00-%m-%d')))
)) > 0,CONCAT(@years,' an',IF(@years>1,'s','')),''),IF(@years >= 21, '', CONCAT(
IF((@months := FLOOR((@days := DATEDIFF(NOW(),DATE_FORMAT($dateField,
CONCAT(YEAR(CURRENT_DATE()) - @postBD,'-%m-%d')))) / 30.4375)) >= 0
,CONCAT(' ',@months,' mois'),''),IF(@years >= 3, '', CONCAT(' '
,(@days := FLOOR(MOD(@days, 30.4375))),CONCAT(' jour',IF(@days>0,'s',''))
))))) USING utf8) AS `$alias`
SQL
		);
//SELECT
//
//#Last birthday
//CONVERT(CONCAT(
//IF(
//  (@years := (
//    DATE_FORMAT(NOW(), '%Y') - DATE_FORMAT(date_birth, '%Y')
//    - (DATE_FORMAT(NOW(), '00-%m-%d') < DATE_FORMAT(date_birth, '00-%m-%d'))
//  )) > 0
//  ,CONCAT(@years,' an',IF(@year>1,'s ',' '))
//  ,''
//)
//
//,IF(@years > 21, '', CONCAT(
//    (@months := FLOOR(
//      (@days := DATEDIFF(
//        NOW(),
//        DATE_FORMAT(date_birth, CONCAT(YEAR(CURRENT_DATE()),'-%m-%d'))
//      )) / 30.4375
//    ))
//    ,' mois '
//
//    ,IF(@years > 3, '', CONCAT(
//      (@days := FLOOR(MOD(@days, 30.4375)))
//      ,CONCAT(' jour',IF(@days>0,'s',''))
//    ))
//))
//
//) USING utf8)
//
//
//AS âge
//
//FROM contacts
//WHERE prenom LIKE 'kim'

// <editor-fold defaultstate="collapsed" desc="Old functions">
//		return new SqlVariable(
//			"(SELECT (DATE_FORMAT(FROM_DAYS(TO_DAYS(NOW())-TO_DAYS($dateField)), '%y ans %c mois, %e jours'))) AS `$alias`"
//		);
////		return new SqlVariable(
////				"(SELECT IF($dateField = 0 OR $dateField IS NULL, NULL, "
////				. "DATE_FORMAT(NOW(), '%Y') - DATE_FORMAT($dateField, '%Y')"
////				. "- (DATE_FORMAT(NOW(), '00-%m-%d') < DATE_FORMAT($dateField, '00-%m-%d'))"
////				. ")) AS age"
////		);
// </editor-fold>
	}
}

class SqlVariable implements SqlVar {

	protected $code;

	function __construct($code) {
		$this->code = $code;
	}

	function buildSql($defaultTable, &$bindings) {
		return $this->code;
	}

	public function __toString() {
		return $this->code;
	}
}

class SqlSetField extends SqlVariable {

	function __construct(ModelTableQuery $q, $field) {
		$this->code = $q->table->getRelationInfo($field)->getNameClause($q);
	}
}

class SqlBindingVariable implements SqlVar {

	public $bindings;
	public $code;

	function __construct($code = null, array $bindings = null) {
		$this->bindings = $bindings;
		$this->code = $code;
	}

	public function buildSql($defaultTable, &$bindings) {
		if (is_array($this->bindings) && count($this->bindings) > 0) {
			$bindings = array_merge($bindings, $this->bindings);
		}
		return $this->code;
	}
}

abstract class QuerySelectBase extends SqlVariable {

	/** @var ModelTableQuery */
	protected $query;
	private $alias = null;

	function __construct(ModelTableQuery $query, $alias = null) {
		$this->alias = $alias;
		$this->query = $query;
	}

	public function setAlias($alias) {
		$this->alias = $alias;
		return $this;
	}

	protected function getQualifiedName($field) {
		return $this->query->getQualifiedName($field);
	}

	abstract protected function doBuildSql(ModelTableQuery $query, &$bindings);

	final public function buildSql($defaultTable, &$bindings) {
		return $this->doBuildSql($this->query, $bindings)
				. ($this->alias !== null ? " AS `$this->alias`" : null);
	}
}

class QuerySelect extends SqlVariable {

	public $colName_s, $alias_es;
	/** @var ModelTable */
	public $table;

	public function __construct($col_s, $alias_es = null, $table = null) {
		$this->colName_s = $col_s;
		$this->alias_es = $alias_es;
		$this->table = $table;
	}

	public function __toString() {
		return $this->buildSql(false, $bindings);
	}

	public static function create($col_s, $alias_es = null, $table = null) {
		return new QuerySelect($col_s, $alias_es, $table);
	}

	function isSetTable() {
		return $this->table !== null;
	}

	function isSetAlias() {
		return $this->alias_es != null;
	}
	
	function isTable($table) {
		if ($this->table instanceof ModelTable) {
			if ($table instanceof ModelTable) return $this->table === $table;
			else return $this->table->getDBTable() === $table;
		} else {
			if ($table instanceof ModelTable) return $this->table === $table->getDBTable();
			else return $this->table === $table;
		}
	}

	/**
	 *
	 * @param mixed $defaultTableName	TRUE: means that this select's table
	 * is the default one, it will be omited. FALSE: means that the table name
	 * must be included. Finally, a tableName (or a ModelTable) can be precised,
	 * and the table name will be omited only if this clause's table is the same.
	 */
	public function buildSql($defaultTableName = true, &$bindings) {
		// Cannot really group, because the type of the param must be excluded
		// to be boolean first...
		if ($this->table === null) $omit = true;
		else if ($defaultTableName === true) $omit = true;
		else if ($defaultTableName === false) $omit = false;
		else if ($this->isTable($defaultTableName)) $omit = true;
		else $omit = false;

		$tableName = $this->table instanceof ModelTable ? $this->table->getDBTable() : $this->table;
		$qTable = $omit ? null : Query::quoteName($tableName);
		
		return $this->doBuildSql($qTable);
	}

	protected function doBuildSql($qTable) {
		$parts = array();

		if ($this->colName_s === '*' || $this->colName_s === null) {
			if ($this->alias_es !== null) {
				if ($this->table instanceof ModelTable == false)
					throw new IllegalStateException('Cannot select * with alias if $table is not a ModelTable');

				foreach ($this->table->getColumns() as $col) {
					$col instanceof ModelColumn;
					$parts[] = $col->buildSelect($qTable, false, $this->alias_es, true);
				}
			} else {
				$parts[] = '*';
			}
		} else if (is_array($this->colName_s)) {
			if (is_array($this->alias_es)) {
				if (count($this->alias_es) == count($this->colName_s)) {
					foreach ($this->colName_s as $i => $colName) {
						$parts[] = ModelColumn::buildColumnSelect($colName,
								$qTable, false, $this->alias_es[$i], false);
					}
				} else {
					foreach ($this->colName_s as $i => $colName) {
						$alias = array_key_exists($colName, $this->alias_es) ?
								$this->alias_es : null;
						$parts[] = ModelColumn::buildColumnSelect($colName,
								$qTable, false, $alias, false);
					}
				}
			}
		} else {
			return ModelColumn::buildColumnSelect(
					$this->colName_s, $qTable, false, $this->alias_es, false);
		}

		return implode(', ', $parts);
	}
}

class QuerySelectRaw extends QuerySelect {

	protected $code;

	public function __construct($code) {
		$this->code = $code;
	}

	public static function create($code) {
		return new QuerySelectRaw($code);
	}

	function buildSql($defaultTable, &$bindings) {
		return $this->code;
	}
}

class QuerySelectFunctionOnField extends QuerySelectBase {

	/** @var ModelTableQuery */
	private $field;
	private $fn;

	function __construct(ModelTableQuery $query, $field, $fn, $alias = null) {
		parent::__construct($query, $alias);
		$this->field = $field;
		$this->fn = $fn;
	}

	protected function doBuildSql(ModelTableQuery $query, &$bindings) {
		if (is_array($this->fn)) {
			$r = $query->getQualifiedName($this->field);
			foreach ($this->fn as $fn) {
				$r = "$fn($r)";
			}
			return $r;
		} else {
			return "$this->fn(" . $query->getQualifiedName($this->field) . ")";
		}
	}
}

class QuerySelectSum extends QuerySelectFunctionOnField {

	function __construct(ModelTableQuery $query, $field, $alias = null) {
		parent::__construct($query, $field, 'SUM', $alias);
	}
}

class QuerySelectSub extends QuerySelect {

	/** @var Query */
	public $query;
	public $alias;

	public function __construct(Query $query, $alias = null) {
		$this->query = $query;
		$this->alias = $alias;
	}

	public function buildSql($defaultTable, &$bindings) {
		$sql = $this->query->buildSql($defaultTable, $bindings);
		// Remove potential trailing comma ;
		if (substr($sql, -1) === ';') $sql = substr($sql, 0, -1);
		return "($sql)"
				. ($this->alias !== null ? " AS `$this->alias`" : null);
	}
}

class QueryFormattedSelect extends QuerySelect {

	protected $format;
	protected $alias;
	/** @var QueryAliasable */
	protected $query;

	public function __construct(QueryAliasable $query, $alias, $format, $table = null) {
		$this->table = $table;
		$this->format = $format;
		$this->alias = $alias;
		$this->query = $query;

		// Force joins creation
		return $this->buildSql(false, $bindings);
	}

	public static function createClause(QueryAliasable $query, $format, $dbTable) {
		$regex = '/%([^%]+)%/';

		preg_match_all($regex, $format, $matches);

		$fields = $matches[1];

		$glueParts = preg_split($regex, $format);

		$qTable = $dbTable !== null ? Query::quoteName($dbTable) . '.' : null;

		$parts = array();
		for ($i=0, $l=count($fields); $i<$l; $i++) {
			if ($glueParts[$i] !== '') {
				$parts[] = "'" . str_replace("'", "\\'", $glueParts[$i]) . "'";
			}
			$parts[] = $query->getQualifiedName($fields[$i]);
//			$parts[] = "$qTable`$fields[$i]`";
		}
		if (isset($glueParts[$i])) {
			$parts[] = "'" . str_replace("'", "\\'", $glueParts[$i]) . "'";
		}

		return 'CONVERT(CONCAT(' . implode(', ', $parts) . ") USING utf8)";
	}

	protected function doBuildSql($qTable) {
		return self::createClause($this->query, $this->format, $qTable) . " AS `$this->alias`";
//		$regex = '/%([^%]+)%/';
//
//		preg_match_all($regex, $this->format, $matches);
//
//		$fields = $matches[1];
//
//		$glueParts = preg_split($regex, $this->format);
//
//		$qTable = $qTable !== null ? "$qTable." : null;
//
//		$parts = array();
//		for ($i=0, $l=count($fields); $i<$l; $i++) {
//			if ($glueParts[$i] !== '') {
//				$parts[] = "'" . str_replace("'", "\\'", $glueParts[$i]) . "'";
//			}
//			$parts[] = "$qTable`$fields[$i]`";
//		}
//		if (isset($glueParts[$i])) {
//			$parts[] = "'" . str_replace("'", "\\'", $glueParts[$i]) . "'";
//		}
//
//		return 'CONCAT(' . implode(', ', $parts) . ") AS `$this->alias`";
	}
}

class SqlFunction {

	private $fn;

	public function __construct($fn) {
		$this->fn = $fn;
	}

	public function getString() {
		return $this->fn;
	}

}

interface SqlVar {
	function buildSql($defaultTable, &$bindings);
}

class QueryErrorHandler {

	private static $instance = null;

	public static function getInstance() {
		if (self::$instance === null) self::$instance = new QueryErrorHandler();
		return self::$instance;
	}

	public static function process(Query $query = null, $error) {
		Logger::get('QueryErrorHandler')->debug("Query error message: $error[2]");
		switch ($error[1]) {
			case 1062:
				if (preg_match("/^Duplicate entry '([^']+)' for key '([^']+)'$/",
						$error[2], $matches)) {
					$value = $matches[1];
					$key = $matches[2];
					$message = lang("La valeur '%value%' doit être unique mais elle existe déjà.", $value);
				} else {
					$message = lang('Une des valeur entrée doit être unique.');
				}
				throw new SqlUserException(
					$message,
					lang('Erreur : valeur duppliquée')
				);

			case 1050:
				if (preg_match("/^Table '([^']+)' already exists$/",
						$error[2], $matches)) {
					$message = lang("La table '%table%' existe déjà", $matches[1]);
				} else {
					$message = lang('Impossible de créer la nouvelle table');
				}
				throw new SqlUserException($message, lang('Impossible de créer la table'));

			default:
				$sql = $query !== null ? PHP_EOL . $query->getSqlString() : null;
				throw new SqlSystemException($error[2] . $sql);
		}
	}
}

interface QueryAliasable {

	function getQualifiedName($fieldName, $table = QueryJoin::TABLE_RIGHT);

	function convertQualifiedNames($preSql, &$bindings);

	/**
	 * Make the given relation name relative to the alisable. The validity or
	 * existence of the relation itself will not be checked; all that method
	 * does is to prepend the correct prefix to make the given name relative
	 * to iself.
	 * 
	 * This method will only work with relation names, and produce chained
	 * relation aliases. Use {@link QueryAliasable::getQualifiedName()} in
	 * order to get name of fields in SQL format.
	 * 
	 * @see QueryAliasable::getRelationInfo() to directly access a relation's
	 * info object (thus, ensuring the relation actually exists).
	 * @see 
	 */
	function makeRelationName($targetRelationName);

	function getRelationInfo($targetRelationName, $requireType = false);

	/** @return QueryWhere */
	function createWhere($conditions = null, $inputs = null);

	/** @return ModelTableQuery */
	function getQuery();

	/** @return arary */
	function &getContext();
}
