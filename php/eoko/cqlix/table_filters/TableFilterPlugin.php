<?php

namespace eoko\cqlix\table_filters;

use ModelTable, ModelTableQuery;

use Logger;
use IllegalArgumentException, IllegalStateException;

class TableFilterPlugin {

	/** @var ModelTable */
	private $table;

	private $filters;
	private $internalFilters;
	private $filterOptions = null;

	private $baseRelations;

	function __construct($table, $baseRelations = null) {
		$this->table = $table;
		$this->baseRelations = $baseRelations;
	}

	/**
	 * Gets the TableFilterPlugin in the given table.
	 * @param string|ModelTable $table
	 * @return TableFilterPlugin
	 */
	public static function getFrom($table) {
		return ModelTable::getTable($table)->getPlugin(get_called_class());
	}

	public function getFilters() {
		return $this->filters;
	}

	public function hasFilterOptions() {
		return $this->filterOptions !== null;
	}

	public function getFilterOptions($visible = true) {
		return $this->filterOptions;
	}

	public function getFilterTokenizer() {
		return __NAMESPACE__ . "\\makeToken";
	}

	public function makeToken() {
		if (func_num_args() === 1) {
			return "%%$this->table::" . func_get_arg(0) . '%%';
		} else if (func_num_args() === 2) {
			list($class, $filter) = func_get_args();
			return "%%$class::$filter%%";
		} else {
			throw new IllegalArgumentException('Wrong number of arguments (must be 1 or 2)');
		}
	}

	/**
	 *
	 * @param string $name
	 * @param string $label
	 * @param string $condition
	 * @return TableFilter
	 */
	public function addFilter($filter, $label = null, $condition = null, $default = false) {

		if (false === $filter instanceof TableFilter) {
			$filter = new TableFilter($filter, $label, $condition, $default);
		}

		return $this->filters[$filter->name] = $filter;
	}

	public function addInternalFilter($filter, $label = null, $condition = null) {

		if (false === $filter instanceof TableFilter) {
			$filter = new TableFilter($filter, $label, $condition, false);
		}

		return $this->internalFilters[$filter->name] = $filter;
	}

	public function addFilterOption($filter, $label = null, $onCondition = null,
			$offCondition = null, $default = true) {

		if (false === $filter instanceof TableFilter) {
			$filter = new TableFilterOption($filter, $label, $onCondition, $offCondition, $default);
		}

		return $this->filterOptions[$filter->name] = $filter;
	}

	/**
	 *
	 * @param ModelTable|string $table
	 * @param TableFilter $filter
	 * @return TableFilter
	 */
	public static function getFilter($table, $filter) {
		if ($filter instanceof TableFilter) return $filter;
		$plugin = self::getFrom($table);
//		$filter = constant($plugin->table->tableName . "::$filter");
		if (isset($plugin->filters[$filter])) {
			return $plugin->filters[$filter];
		} else if (isset($plugin->internalFilters[$filter])) {
			return $plugin->internalFilters[$filter];
		} else if (isset($plugin->filterOptions[$filter])) {
			return $plugin->filterOptions[$filter];
		} else {
			throw new IllegalArgumentException("Table $table has no filter $filter");
		}
	}

	public function addLoadQueryFilters(ModelTableQuery $query, $filters, $baseRelation = null) {

		if (!$filters) return;

		$opts = helper\makeFilterHash($filters);

		if (!isset($opts['all'])) {
			$conditions = null;

			$myFilters = null;

			foreach ($filters as $filter) {
				if (isset($this->filters[$filter])) {
					$myFilters[] = $filter;
				}
			}

			if ($myFilters && count($myFilters) !== count($this->filters)) {
				foreach ($myFilters as $filter) {
					$conditions[] = $this->filters[$filter]->getCondition(
						$baseRelation, $this->baseRelations, $this->table, $opts
					);
				}

				if ($conditions) {
					$query->andWhere('(' . implode(' OR ', $conditions) . ')');
				}
			}
		}

		$options = null;
		if ($this->filterOptions) foreach ($this->filterOptions as $filter) {
			$o = $filter->getCondition($baseRelation, $this->baseRelations, $this->table, $opts);
			if ($o) $options[] = $o;
		}

		if ($options) {
			$query->andWhere('(' . implode(' AND ', $options) . ')');
		}
	}
}
