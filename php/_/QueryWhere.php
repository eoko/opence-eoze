<?php
/**
 * @author Éric Ortéga <eric@mail.com>
 * @copyright Copyright (c) 2010, Éric Ortéga
 * @license http://www.planysphere.fr/licenses/psopence.txt
 * @package PS-ORM-1
 * @subpackage Query
 */

require_once __DIR__ . '/Query.php';

class QueryWhere {

	protected $sql = null;
	private $bindings = array();
	/** @var QueryAliasable */
	public $aliasable;

	public function isNull() {
		return $this->sql === null;
	}

	/**
	 * @return QueryWhere
	 */
	public static function create(QueryAliasable $aliasable, $condition = null, $inputs = null) {
		return new QueryWhere($aliasable, $condition, $inputs);
	}

	public function __construct(QueryAliasable $aliasable, $condition = null, $inputs = null) {
		$this->aliasable = $aliasable;
		if ($condition !== null) $this->where($condition, $inputs);
	}

	/**
	 * Set the where clause of this search. If any previous conditions have
	 * been set, they will be overwritten by this method call.
	 *
	 * The condition must be specified by a valid SQL condition, with input
	 * values replaced by '?':<br/>
	 * eg. 'id = ?', 'name LIKE ?'
	 *
	 * Note: to use SQL joker characters when searching on a string column,
	 * the jokers must be put in the <b>$inputs</b> argument:<br/>
	 * eg. <code>where('name LIKE ?', '__big%')</code>
	 *
	 * @param String $condition
	 * @param mixed $inputs,...
	 * @return QueryWhere
	 */
	public function where($condition, $inputs = null) {
		if (func_num_args() > 2) $inputs = array_slice(func_get_args(), 1);
		$this->sql = null;
		$this->bindings = array();
		return $this->pushCondition($condition, $inputs, null);
	}

	/**
	 * @return QueryWhere
	 */
	private function pushInCondition($field, $values, $op, $not = '') {

		if ($this->sql !== null) {
			$this->sql .= " $op ";
		}

		$field = $this->aliasable->getQualifiedName($field);

		if ($field instanceof SqlVariable) {
			// (?) It has not been tested that the bindings were functionnal
			$quoted = $field->buildSql(false, $this->bindings);
		} else {
			$quoted = Query::quoteName($field);
		}

		if (!is_array($values)) {
			$values = array($values);
		}

		if (count($values) > 0) {
			$this->sql .= $quoted . $not . ' IN (?' . str_repeat(',?', count($values) - 1) . ')';
			$this->bindings = array_merge($this->bindings, $values);
		} else {
			$this->sql .= $quoted . $not . ' IN ())';
		}
		return $this;
	}

	/**
	 * @return QueryWhere
	 */
	public function whereIn($field, $values = array()) {
		if (func_num_args() > 2) $values = array_splice(func_get_args(), 1);
		$this->sql = null;
		return $this->pushInCondition($field, $values, null);
	}

	/**
	 * @return QueryWhere
	 */
	public function whereNotIn($field, $values = array()) {
		if (func_num_args() > 2) $values = array_splice(func_get_args(), 1);
		$this->sql = null;
		return $this->pushInCondition($field, $values, null, ' NOT');
	}

	/**
	 * @return QueryWhere
	 */
	public function andWhereIn($field, $values = array()) {
		if (func_num_args() > 2) $values = array_splice(func_get_args(), 1);
		return $this->pushInCondition($field, $values, 'AND');
	}

	/**
	 * @return QueryWhere
	 */
	public function andWhereNotIn($field, $values) {
		if (func_num_args() > 2) $values = array_splice(func_get_args(), 1);
		return $this->pushInCondition($field, $values, 'AND', ' NOT');
	}

	/**
	 * @return QueryWhere
	 */
	public function orWhereIn($field, $values) {
		if (func_num_args() > 2) $values = array_splice(func_get_args(), 1);
		return $this->pushInCondition($field, $values, 'OR');
	}

	/**
	 * @return QueryWhere
	 */
	public function orWhereNotIn($field, $values) {
		if (func_num_args() > 2) $values = array_splice(func_get_args(), 1);
		return $this->pushInCondition($field, $values, 'OR',' NOT');
	}

	/**
	 * @return QueryWhere
	 */
	private function pushCondition($condition, $inputs, $op) {
		if ($condition instanceof QueryWhere) {
			if ($condition->isNull()) {
				return $this;
			}
			if ($this->sql !== null) {
				$this->sql = "($this->sql) $op ";
			}
			$this->sql .= '(' . $condition->buildWhere($this->bindings) . ')';
		} else if ($condition instanceof SqlVar) {
			throw new UnsupportedOperationException('bugged');
			// buildSql must be modified to use an aliasable instead of a table name ...
			$this->sql .= '(' . $condition->buildSql($this->aliasable->table, $this->bindings) . ')';
		} else {
			if ($this->sql !== null) {
				$this->sql .= " $op ";
			}
			$this->sql .= '(' . $this->aliasable->convertQualifiedNames($condition, $this->bindings) . ')';
			if ($inputs !== null) {
				if (is_array($inputs)) $this->bindings = array_merge($this->bindings, $inputs);
				else array_push($this->bindings, $inputs);
			}
		}
		return $this;
	}

	public function buildSql(&$bindings) {
		$bindings = array_merge($bindings, $this->bindings);
		return $this->sql;
	}

	public function buildWhere(&$binds) {
		return $this->buildSql($binds);
	}

	/**
	 *
	 * @param <type> $conditions
	 * @param <type> $___
	 * @return QueryWhere
	 */
	public function andWhere($condition, $inputs = null) {
		if (func_num_args() > 2) $inputs = array_slice(func_get_args(), 1);
		return $this->pushCondition($condition, $inputs, 'AND');
	}

	/**
	 *
	 * @param <type> $conditions
	 * @param <type> $inputs
	 * @return QueryWhere
	 */
	public function orWhere($condition, $inputs = null) {
		if (func_num_args() > 2) $inputs = array_slice(func_get_args(), 1);
		return $this->pushCondition($condition, $inputs, 'OR');
	}
}
