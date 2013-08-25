<?php

class QueryJoinLeft extends QueryJoin {

	/**
	 * Create a new LEFT JOIN.
	 *
	 * @param Query $query				the query the join belongs to. Will be
	 * used for reference, and getting info on the query; the constructor will
	 * NOT push the join into the query
	 *
	 * @param mixed $foreignTable		the name of the foreign (ie. right) table
	 * (that is, one that is NOT in the FROM clause of the Query)
	 *
	 * @param string $leftField			the name of the left table's field used
	 * to make the join
	 *
	 * @param string $rightField		the name of the right table's field
	 * used to make the join
	 *
	 * @param string $alias				the alias to be used to refer to the
	 * foreign table. If none given, one will be generated automatically with
	 * {@link Query::getNextJoinAlias()}. If one is given, it will be expected
	 * to be unique (that will not be checked, the crash will come from the
	 * database if it is not).
	 *
	 * @param string $comparisonOperator	the comparison operator (any valid
	 * SQL string is accepted -- this param is not checked for validity before
	 * being sent to the database)
	 *
	 * @param string|ModelTableProxy $leftTable			the name of the left table of the JOIN.
	 * If not specified, it will be expected that the FROM clause of the Query has
	 * been set on one, and only one, table -- or crash. If the table is
	 * selected with an alias, that is this alias that must be given.
	 *
	 * @return QueryJoin self (the query can be chain-continued by filling the select
	 * clause of the join with {QueryJoin::select()} which returns the original
	 * Query.
	 */
	public function __construct(Query $query, ModelTableProxy $rightTable, $leftField,
			$rightField = null, $alias = null,
			ModelTableProxy $leftTable = null, $leftTableAlias = null,
			$buildOnClauseFn = null) {

		parent::__construct($query, $rightTable, $leftField, $rightField,
				$alias, $leftTable, $leftTableAlias);

		$this->buildOnClauseFn = $buildOnClauseFn;
	}

	private $buildOnClauseFn = null;

	private function buildSingleField($field, $tableName, $side) {
		if ($field instanceof QueryJoinField) {
			return $field->buildField($tableName);
		} else {
			return $this->getQualifiedName($field, $side);
		}
	}

	protected function buildOnClause() {

		$leftField = $this->buildSingleField($this->leftField, $this->leftTableAlias, QueryJoin::TABLE_LEFT);
		$rightField = $this->buildSingleField($this->rightField, $this->foreignTableAlias, QueryJoin::TABLE_RIGHT);

		if ($this->buildOnClauseFn !== null) {
			return call_user_func($this->buildOnClauseFn, $leftField, $rightField);
		} else {
			return "$leftField = $rightField";
		}
	}

	protected function buildJoin() {

		$where = count($this->where) === 0 ? null :
				implode(' ', $this->where) . ' ';

//REM		$leftField = $this->leftField instanceof QueryJoinField ?
//				$this->leftField->buildField($this->leftTableAlias)
//				: "`$this->leftTableAlias`.`$this->leftField`";
//
//		$rightField = $this->rightField instanceof QueryJoinField ?
//				$this->rightField->buildField($this->foreignTableAlias)
//				: "`$this->foreignTableAlias`.`$this->rightField`";

		$onClause = $this->buildOnClause();

		return "LEFT JOIN `$this->foreignDBTableName`"
				. ($this->foreignTableAlias !== $this->foreignDBTableName ? " AS $this->qForeignTableAlias" : null)
				. " ON $onClause"
//REM				. " ON $leftField = $rightField"
				. $where
				;
	}

	public function whereAssoc($assocField, $value = null, $boolOp = 'AND', $operator = '=') {
		if ($value !== null) {
			$this->where[] = " $boolOp `$this->foreignTableAlias`.`$assocField` $operator ?";
			$this->bindings[] = $value;
		} else {
			$this->where[] = " $boolOp $assocField";
		}
	}

	public function andWhere($condition, $inputs = null) {
		$where = new QueryWhere($this, $condition, $inputs);
		if (!$where->isNull()) {
			$this->where[] = ' AND ' . $where->buildSql($this->bindings);
		}
	}
}
