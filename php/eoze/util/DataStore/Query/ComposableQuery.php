<?php

namespace eoze\util\DataStore\Query;

use eoze\util\DataStore;
use eoze\util\DataStore\Query;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 2 nov. 2011
 */
class ComposableQuery implements Query {

	private $filter;

	private $sorter;

	private $pager;

//	public function options(DataStore $store, &$filter, &$sorter, &$pager) {
//		$this->setRepository($store);
//		$filter = $this->filter;
//		$sorter = $this->sorter;
//		$pager  = $this->pager;
//	}

	public function getOptions(DataStore $store) {
		$this->setRepository($store);
		return array(
			self::FILTER => $this->filter,
			self::PAGER  => $this->pager,
			self::SORTER => $this->sorter,
		);
	}

	protected function setRepository(DataStore $store) {
		if ($this->filter) {
			$this->filter->setRepository($store);
		}
		if ($this->sorter) {
			$this->sorter->setRepository($store);
		}
		if ($this->pager) {
			$this->pager->setRepository($store);
		}
	}

	public function setPager(Pager $pager) {
		$this->pager = $pager;
	}

	public function setSorter(Sorter $sorter) {
		$this->sorter = $sorter;
	}

	public function addSorter(Sorter $sorter) {
		if (!$this->sorter) {
			$this->sorter = $sorter;
		} else if ($this->sorter instanceof CompositeSorter) {
			$this->sorter->add($sorter);
		} else {
			$this->sorter = new CompositeSorter($this->sorter, $sorter);
		}
	}

	public function setFilter(Filter $filter) {
		$this->filter = $filter;
	}

	public function addFilter(Filter $filter) {
		if (!$this->filter) {
			$this->filter = $filter;
		} else if ($this->filter instanceof CompositeFilter) {
			$this->filter->add($filter);
		} else {
			$this->filter = new CompositeFilter($this->filter, $filter);
		}
	}

}
