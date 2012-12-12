<?php

namespace eoko\cqlix\table_filters;

class TableFilterOption extends TableFilter {

	private $onCondition, $offCondition;

	public function __construct($name, $label, $onCondition, $offCondition, $default) {
		$this->onCondition = $onCondition ? "($onCondition)" : '1';
		$this->offCondition = $offCondition ? "($offCondition)" : '1';
		parent::__construct($name, $label, null, $default);
	}

	protected function getConditionString($opts) {
		return isset($opts[$this->name]) 
				? $this->getOnCondition($opts) 
				: $this->getOffCondition($opts);
	}

	protected function getOnCondition($opts) {
		return $this->onCondition;
	}

	protected function getOffCondition($opts) {
		return $this->offCondition;
	}
}
