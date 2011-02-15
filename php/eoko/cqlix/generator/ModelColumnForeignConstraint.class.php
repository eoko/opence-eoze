<?php

class ModelColumnForeignConstraint {

	public $targetTable;
	public $targetField;
	public $constraintName;

	function __construct($targetTable, $targetField, $indexName) {
		$this->targetTable = $targetTable;
		$this->targetField = $targetField;
		$this->constraintName = $indexName;
	}
}
