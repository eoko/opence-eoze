<?php

namespace eoko\cqlix\generator;

class ModelColumnForeignConstraint {

	public $targetTable;
	public $targetField;
	public $constraintName;
	
	public $onDelete;
	public $onUpdate;

	function __construct($targetTable, $targetField, $indexName) {
		$this->targetTable = $targetTable;
		$this->targetField = $targetField;
		$this->constraintName = $indexName;
	}
}
