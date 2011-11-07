<?php

class QueryJoinAssocMirror extends QueryJoinAssoc {

	function __construct(Query $query,
			ModelTableProxy $targetTable,
			$assocTable, $leftAssocField, $rightAssocField = null,
			$alias = null, $leftTableAlias = null) {

		if ($leftTableAlias === null) {
			parent::__construct(
				$query,
				$targetTable, $targetTable, $assocTable,
				$leftAssocField, $rightAssocField, $alias
			);
		} else {
			$targetTable = $targetTable->getInstance();
			$leftPkName = $targetTable->getPrimaryKeyName();
			parent::__construct(
				$query,
				$leftTableAlias, $targetTable, $assocTable,
				$leftAssocField, $rightAssocField, $alias,
				$leftPkName, $leftPkName
			);
		}
	}

	protected function buildJoin() {

		$thisField = "`$this->leftTableAlias`.`$this->leftField`";
		$otherField = "$this->qForeignTableAlias.`$this->leftField`";

		$assocField2 = $assocField1 = $this->assocTableAlias !== null ?
				"`$this->assocTableAlias`" : "`$this->assocTableName`";

		$assocField1 .= ".`$this->leftAssocField`";
		$assocField2 .= ".`$this->rightAssocField`";

		// --- Where ---
		$where = "$thisField = $assocField1 OR $thisField = $assocField2";
		if (count($this->where) > 0) {
			$where = "($where)" . implode('', $this->where);
		}

		return "LEFT JOIN `$this->foreignDBTableName` AS $this->qForeignTableAlias "
				. "ON $otherField = (SELECT IF($thisField = $assocField1, $assocField2, $assocField1) "
				. "FROM `$this->assocTableName` AS `$this->assocTableAlias` WHERE $where)"
				;
	}

}