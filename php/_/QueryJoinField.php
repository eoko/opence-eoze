<?php

/**
 * @deprecated Programmed for removal from the framework.
 */
interface QueryJoinField {

	function buildField($tableName);

}

/**
 * @deprecated Programmed for removal from the framework.
 */
abstract class QueryJoinField_Base implements QueryJoinField {

	public static function buildSingleField($field, $tableName) {
		if ($field instanceof QueryJoinField) {
			return $field->buildField($tableName);
		} else {
			return "`$tableName`.`$field`";
		}
	}
}

/**
 * @deprecated Programmed for removal from the framework.
 */
class QueryJoinField_SimpleFunction extends QueryJoinField_Base {

	private $field;
	private $fnSql;

	public function __construct($field, $fnSql) {
		$this->field = $field;
		$this->fnSql = $fnSql;
	}

	public function buildField($tableName) {
		$field = $this->buildSingleField($this->field, $tableName);
		return "$this->fnSql($field)";
	}
}

/**
 * @deprecated Programmed for removal from the framework.
 */
class QueryJoinField_Multiple extends QueryJoinField_Base {

	private $fields;

	public function __construct(array $fields) {
		$this->fields = $fields;
	}

	public function buildField($tableName) {
		$r = array();
		foreach ($this->fields as $field) {
			$r[] = $this->buildSingleField($field, $tableName);
		}
		return $r;
	}

}
