<?php

use eoko\cqlix\legacy\QueryAliasableToAliaser;

abstract class QueryJoin implements QueryAliasable {

	const TABLE_LEFT = 1;
	const TABLE_RIGHT = 2;
	const TABLE_ASSOC = 3;

	const TABLE_LOCAL = self::TABLE_LEFT;
	const TABLE_FOREIGN = self::TABLE_RIGHT;

	/** @var ModelTableQuery */
	public $query;

	private $select = array();

	/** @var ModelTable or table name */
	protected $foreignTable;
	protected $foreignDBTableName;
	public $foreignTableAlias;
	protected $qForeignTableAlias;

	/**
	 * @var ModelTable
	 */
	protected $leftTable;
	protected $leftTableAlias;

	protected $leftField, $rightField, $comparisonOperator;

	protected $where = array();
	protected $bindings = array();

	public function __toString() {
		return get_class($this)
				. ": $this->leftTable AS $this->leftTableAlias -> $this->foreignTable AS $this->foreignTableAlias"
				. " ON $this->leftTableAlias->$this->leftField = $this->foreignTableAlias->$this->rightField"
				;
	}

	/**
	 * Clones this QueryJoin object.
	 */
	public function __clone() {
		$clone = array();
		foreach ($this->select as $key => $value) {
			$clone[$key] = is_object($value) ? clone $value : $value;
		}
		$this->select = $clone;
	}

	/**
	 * Create a new JOIN. This constructor will NOT append the join to the query.
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
	 * @param string $foreignTableAlias				the alias to be used to refer to the
	 * foreign table. If none given, one will be generated automatically with
	 * {@link Query::getNextJoinAlias()}. If one is given, it will be expected
	 * to be unique (that will not be checked, the crash will come from the
	 * database if it is not).
	 *
	 * @param string $comparisonOperator	the comparison operator (any valid
	 * SQL string is accepted -- this param is not checked for validity before
	 * being sent to the database)
	 *
	 * @param string $leftTable			the name of the left table of the JOIN.
	 * If not specified, it will be expected that the FROM clause of the Query has
	 * been set on one, and only one, table -- or crash. If the table is
	 * selected with an alias, that is this alias that must be given.
	 */
	protected function __construct(
		ModelTableQuery $query,
		ModelTableProxy $foreignTable,
		$leftField,
		$rightField = null,
		$foreignTableAlias = null,
		ModelTableProxy $leftTable = null, $leftTableAlias = null
	) {

		$this->foreignTable = $foreignTable;

		$this->query = $query;

		if ($foreignTable instanceof ModelTableProxy) {
			/** @var $foreignTable ModelTable */
			if ($rightField === null) {
				$rightField = $foreignTable->getPrimaryKeyName();
			}
			$this->foreignDBTableName = $foreignTable->getDBTable();
		} else if ($rightField === null) {
			throw new IllegalArgumentException(
				'$referencedField must be precised if $foreignTable is not given '
				. 'as a ModelTableProxy (' . get_class($foreignTable) . ')'
			);
		}
		$this->foreignTableAlias = $foreignTableAlias === null ?
				$query->getNextJoinAlias($this->foreignDBTableName) : $foreignTableAlias;

		$this->qForeignTableAlias = Query::quoteName($this->foreignTableAlias);

		$this->leftTable = $leftTable !== null ? $leftTable : $query->table;
		$this->leftTableAlias = $leftTableAlias !== null ? $leftTableAlias : $query->dbTable;

		$this->leftField = $leftField;
		$this->rightField = $rightField;
	}

	abstract protected function buildJoin();

	public function buildSql(&$bindings) {
		if ($this->bindings !== null) {
			$bindings = array_merge($bindings, $this->bindings);
		}
		return $this->buildJoin();
	}

	abstract public function whereAssoc($assocField, $value = null, $boolOp = 'AND', $operator = '=');

	public function andWhere(/** @noinspection PhpUnusedParameterInspection */ $condition, $inputs = null) {
		throw new UnsupportedOperationException();
	}

	/**
	 * Specify the fields to select on the joined (right) table.
	 *
	 * This can be done multiple times, but each call will erase the precedents.
	 *
	 * @param mixed $field_s    can be one field, or an array of fields, or NULL.
	 * If one field is given, one alias is permitted. If NULL, will be selected
	 * (and no alias is permitted). If fields is an array, then one or an array
	 * of alias is permitted (see $alias).
	 *
	 * @internal param mixed $alia_s NULL, one alias, one alias prefix, or an array of alias.
	 * If an alias is given for every $fields, then they must be given in the
	 * same order (ie. the alias for a field must have the same index as this
	 * field). If aliases are given only for a part of the fields, then they
	 * must be given in an associative array which keys are the name of the fields.
	 * If $fields is an array and $alias is a unique string, then it will be
	 * applied as a prefix to all fields' name.
	 *
	 * @throws UnsupportedOperationException
	 * @return Query
	 */
	public function select($field_s = null) {
		if (is_array($field_s)) {
			foreach ($field_s as $k => $fieldName) {

				$fieldAlias = is_string($k) ? $k : null;

				if ($this->foreignTable->hasRelation($fieldName)) {
					/** @var $relationInfo ModelRelationInfo */
					$relationInfo = $this->foreignTable->getRelationInfo($fieldName);
					$relationInfo->selectName(
						$this->query, $fieldAlias, $fieldName
					);
				} else if ($this->foreignTable->hasVirtual($fieldName)) {
					/** @var $virtualField VirtualField */
					$virtualField = $this->foreignTable->getVirtual($fieldName);
					$virtualField->select(
						$this->query, $fieldAlias, $this
					);
				} else if (strstr($fieldName, '->')) {
					throw new UnsupportedOperationException('Select linked fields');
				} else {
					if ($fieldAlias !== null) {
						$this->select[] = "`$this->foreignTableAlias`.`$fieldName` AS `$fieldAlias`";
					} else {
						$this->select[] = "`$this->foreignTableAlias`.`$fieldName`";
					}
				}
			}
		} else if ($field_s === null || $field_s === '*') {
			$this->select[] = "`$this->foreignTableAlias`.*";
		} else {
			$select = "`$this->foreignTableAlias`.`$field_s`";
			$this->select[] = $select;
		}

		return $this;
	}

	public function selectFormatted($alias, $format) {
		$this->select[] = new QueryFormattedSelect($this, $alias, $format, $this->foreignTableAlias);
	}

	public function exitJoin() {
		return $this->query;
	}

	/**
	 * Resets the SELECT clause of this join.
	 */
	public function resetSelect() {
		$this->select = array();
	}

	public function buildSelect(&$bindings) {
		if (count($this->select) == 0) {
			return null;
		}
		foreach ($this->select as $i => $v) {
			if ($v instanceof QuerySelect) {
				$this->select[$i] = $v->buildSql(false, $bindings);
			}
		}
		return implode(', ', $this->select);
	}

	private function getQualifiedNameFor(QueryAliasable $aliasable, ModelTableProxy $table,
			$tableAlias, $field) {

		// This implementation has been copy-pasted from ModeTableQuery. If something
		// seems wrong in here, maybe it should be sensible to go see there if
		// the implemenation has been fixed or what...

		/** @var $table ModelTable */

		if ($table->hasColumn($field)) {
			return "`$tableAlias`.`$field`";
		}
		else if ($table->hasVirtual($field)) {
			return $table
				->getVirtual($field)
				->getClause($this->query, $aliasable);
		}
		else {
			if (count($parts = explode('->', $field)) > 1) {
				$fieldName = array_pop($parts);
				$relationName = implode('->', $parts);
				$relation = $table->getRelationInfo($relationName);
				if ($relation->targetTable->hasRelation($fieldName)) {
					throw new UnsupportedOperationException;
					// relation name
//					$targetRelation = $relation->getRelationInfo($fieldName);
//					return $targetRelation->getNameClause($this, $field);
				} else if ($relation->targetTable->hasVirtual($fieldName)) {
					// virtual
					$leftAlias = $table->getDBTableName() === $tableAlias
						? ''
						: "$tableAlias->";
					return $relation->targetTable
						->getVirtual($fieldName)
						->getClause(
							$this->query,
							$this->query->getJoin("$leftAlias$relationName")
						);
				} else {
					// field
					return $this->query
						->getJoin("{$tableAlias}->$relationName")
						->getQualifiedName($fieldName);
				}
			}
			throw new Exception("No field '$field' in $table");
		}
	}

	public function getQualifiedName($field, $side = QueryJoin::TABLE_RIGHT) {
		switch ($side) {
			case self::TABLE_LEFT:
				return $this->getQualifiedNameFor(
					new QueryJoinAliasable($this, self::TABLE_LEFT),
					$this->leftTable,
					$this->leftTableAlias,
					$field
				);
			case self::TABLE_RIGHT:
				return $this->getQualifiedNameFor(
					$this,
					$this->foreignTable,
					$this->foreignTableAlias,
					$field
				);
			default:
				throw new IllegalArgumentException("Invalid mode: $side");
		}
	}

	/**
	 * Get the RelationInfo for the given relation name, relatively to this
	 * aliasable.
	 *
	 * @param string $relationName
	 * @param bool $requireType
	 * @return ModelRelationInfo
	 */
	public function getRelationInfo($relationName, $requireType = false) {
		return $this->query->table->getRelationInfo(
			"$this->foreignTableAlias->$relationName",
			$requireType
		);
	}

	function makeRelationName($targetRelationName) {
		return "$this->foreignTableAlias->$targetRelationName";
	}

	public function convertQualifiedNames($preSql, &$bindings) {
		return $this->query->doConvertQualifiedNames(
			$preSql,
			new QualifiedNameConverter(
				$this,
				$bindings,
				QueryJoin::TABLE_RIGHT // not implem... just using the most probable case
			)
		);
	}

	/**
	 * @param string|mixed $condition
	 * @param array|null $inputs
	 * @return QueryWhere
	 */
	public function createWhere($condition = null, $inputs = null) {
		return new QueryWhere($this, $condition, $inputs);
	}

	public function getQuery() {
		return $this->query;
	}

	/**
	 * Get the db name of the foreign table.
	 * @return string
	 */
	public function getForeignTableName() {
		return $this->foreignDBTableName;
	}

	/**
	 * Gets the foreign table alias.
	 * @return string
	 */
	public function getForeignTableAlias() {
		return $this->foreignTableAlias;
	}

	public function &getContext() {
		return $this->query->context;
	}

	/**
	 * @inheritdoc
	 */
	public function alias($name) {
		return $this->getQualifiedName($name);
	}

	/**
	 * @inheritdoc
	 */
	public function aliases($clause, array &$bindings = null) {
		return QueryAliasableToAliaser::aliases($this, $clause, $bindings);
	}
}
