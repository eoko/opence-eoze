<?php

namespace eoko\cqlix\table_filters;

use ModelTable;
use IllegalStateException;

class TableFilter {

	public $name;
	public $label;
	public $default;

	private $condition;

	public static function getTokenizer() {
		return __NAMESPACE__ . "\\makeToken";
	}

	public function __construct($name, $label, $condition, $default) {
		$this->name = $name;
		$this->label = $label;
		$this->default = $default;

		$this->condition = "($condition)";
	}

	public function setDefault($default = true) {
		$this->default = $default;
	}

	protected function getConditionString($opts) {
		return $this->condition;
	}

	public function getCondition($baseRelation, $baseRelations, $table, $opts) {

		$br = $baseRelation ? $baseRelation . '->' : '';

		$condition = str_replace(array(
			TOKEN_BASE_RELATION, TOKEN_BASE_RELATION_SHORTCUT
		), $br, $this->getConditionString($opts));

		return preg_replace_callback(
			'/%%([\w:]+)%%/',
			function($m) use ($baseRelation, $br, $baseRelations, $table, $opts) {
				$token = $m[1];
				if (preg_match('/(\w+)::(\w+)/', $token, $m)) {
					list(, $tokenTable, $token) = $m;
					$filter = TableFilterPlugin::getFilter($tokenTable, $token);
					if ("$table" === $tokenTable) {
						return $filter->getCondition($baseRelation, $baseRelations, $table, $opts);
					} else if (isset($baseRelations[$tokenTable])) {
						return $filter->getCondition(
							$br . $baseRelations[$tokenTable], $baseRelations, $tokenTable, $opts
						);
					} else {
						throw new IllegalStateException('Missing base relation for table ' . $m[1]);
					}
				} else {
					return TableFilterPlugin::getFilter($table, $token)->getCondition(
						$baseRelation, $baseRelations, $table, $opts
					);
				}
			},
			$condition
		);
	}

	public function toYaml() {
		$default = $this->default ? 'true' : 'false';
		$option = $this instanceof TableFilterOption ? 'true' : 'false';
		return "{text: $this->label, checked: $default, isOption: $option }";
	}
}
