<?php

namespace eoko\cqlix\table_filters;

use \IllegalArgumentException, \IllegalStateException;

use \ModelTableQuery, \ModelTable;

const TOKEN_BASE_RELATION = '%%BASE_RELATION%%';
const TOKEN_BASE_RELATION_SHORTCUT = '%%BR%%';

function makeToken() {
	if (func_num_args() === 1) {
		return '%%' . func_get_arg(0) . '%%';
	} else if (func_num_args() === 2) {
		list($class, $filter) = func_get_args();
		return "%%$class::$filter%%";
	} else {
		throw new IllegalArgumentException('Wrong number of arguments (must be 1 or 2)');
	}
}

function getFilter(array $filters, $name, $tableRelation, $optConditions = null) {
	if (substr($tableRelation, -2) !== '->') $tableRelation .= '->';
	if (!array_key_exists($name, $filterConditions)) {
		throw new IllegalArgumentException("No condition $name");
	}
}

function getFilters(array $filters = null) {
	$r = array();
	if ($filters === null) return $r;
	foreach ($filters as $name => $filter) {
		$r[$name] = array(
			'label' => $filter[TableHasFilters::LABEL],
			'default' => isset($filter[TableHasFilters::CHECKED]) ?
					$filter[TableHasFilters::CHECKED] : true
		);
	}
	return $r;
}

function addLoadQueryFilters(TableHasFilter $table, ModelTableQuery $query, $filters = null) {

	foreach ($filters as $filter) {
		$condition = $tableFilters[$filter][TableHasFilters::CONDITION];
	}
}

interface TableHasFilters {

//	const CONDITION = 0;
//	const LABEL     = 1;
//	const CHECKED   = 2;
//
//	const FILTER_ALL = 'all';

//	public static function getFilterCondition($name, $opts = null, $baseRelationName = null);

//	public static function addLoadQueryFilters(ModelTableQuery $query, $filters);
}

namespace eoko\cqlix\table_filters\helper;

function makeFilterHash(array $filters) {
	$r = array();
	foreach ($filters as $filter) $r[$filter] = true;
	return $r;
}

function hasFilter(array $filters, $key) {
	foreach ($filters as $filter) {
		if ($filter === $key) return true;
	}
	return false;
}
