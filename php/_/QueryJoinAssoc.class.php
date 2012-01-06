<?php

class QueryJoinAssoc extends QueryJoinLeft {

	protected $assocTableName
		,$assocTableAlias

//REM		,$leftForeignKey
//		,$rightForeignKey

		,$leftAssocField
		,$rightAssocField

		;

	/**
	 *
	 * $leftTable can be given either as a {@link ModelTableProxy} or a string
	 * table alias. If it is given as a string, then the $leftField field
	 * name must be specified or an IllegalArgumentException will be thrown.
	 *
	 * @param Query $query
	 * @param mixeed $leftTable
	 * @param ModelTableProxy $rightTable
	 * @param ModelTableProxy $assocTable
	 * @param string $leftAssocField
	 * @param string $rightAssocField
	 * @param string $alias
	 * @param string $leftField
	 */
	function __construct(Query $query,
			$leftTable, ModelTableProxy $rightTable, $assocTable,
			$leftAssocField, $rightAssocField = null,
			$alias = null, $leftField = null, $rightField = null) {

		$this->assocTableName = $assocTable instanceof ModelTableProxy ?
				$assocTable->getDBTableName() : $assocTable;

		$this->leftAssocField = $leftAssocField;
		$this->rightAssocField = $rightAssocField;

		if ($leftField === null) {
			if ($leftTable instanceof ModelTableProxy) {
				$leftField = $leftTable->getInstance()->getPrimaryKeyName();
			} else {
				throw new IllegalArgumentException(
					'$leftField must be given if $leftTable is not an '
						. 'instance of ModelTableProxy'
				);
			}
		}

		$rightTable = $rightTable->getInstance();
		if ($rightField === null) $rightField = $rightTable->getPrimaryKeyName();

		$this->assocTableAlias = $query->getNextJoinAlias($this->assocTableName);

		parent::__construct(
			$query, $rightTable,
			$leftField, $rightField,
			$alias,
			$leftTable
		);
	}

	public function getQualifiedName($fieldName, $table = QueryJoin::TABLE_RIGHT) {
		if ($table === QueryJoin::TABLE_ASSOC) {
			return "`$this->assocTableAlias`.`$fieldName`";
		} else {
			return parent::getQualifiedName($fieldName, $table);
		}
	}

	protected function buildJoin() {

		$thisField = "`$this->leftTableAlias`.`$this->leftField`";
		$otherField = "$this->qForeignTableAlias.`$this->rightField`";

		$otherAssocField = $thisAssocField = $this->assocTableAlias !== null ?
				"`$this->assocTableAlias`" : "`$this->assocTableName`";

		$thisAssocField .= ".`$this->leftAssocField`";
		$otherAssocField .= ".`$this->rightAssocField`";

		$whereAssoc = count($this->where) === 0 ? null :
				implode(' ', $this->where) . ' ';

		$asAssocAlias = $this->assocTableAlias !== $this->assocTableName ?
				" AS `$this->assocTableAlias`" : null;

		$asAlias = $this->qForeignTableAlias !== "`$this->foreignDBTableName`" ?
				" AS $this->qForeignTableAlias" : null;

		return "LEFT JOIN `$this->assocTableName` $asAssocAlias "
				. "ON $thisField = $thisAssocField "
				. $whereAssoc
				. "LEFT JOIN `$this->foreignDBTableName` $asAlias "
				. "ON $otherField = $otherAssocField"
				;
	}

	public function whereAssoc($assocField, $value = null, $boolOp = 'AND', $operator = '=') {
		if ($value !== null) {
			$this->where[] = " $boolOp `$this->assocTableAlias`.`$assocField` $operator ?";
			$this->bindings[] = $value;
		} else {
			$this->where[] = " $boolOp $assocField";
		}
	}

	public function convertQualifiedNames($preSql, &$bindings) {
		return $this->getQuery()->doConvertQualifiedNames(
			$preSql,
			new QualifiedNameConverter(
				$this,
				$bindings,
				QueryJoin::TABLE_ASSOC
			)
		);
	}
}
