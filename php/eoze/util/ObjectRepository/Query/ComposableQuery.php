<?php

namespace eoze\util\ObjectRepository\Query;

use eoze\util\ObjectRepository;
use eoze\util\ObjectRepository\Query;

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
	
//	public function options(ObjectRepository $repository, &$filter, &$sorter, &$pager) {
//		$this->setRepository($repository);
//		$filter = $this->filter;
//		$sorter = $this->sorter;
//		$pager  = $this->pager;
//	}
	
	public function getOptions(ObjectRepository $repository) {
		$this->setRepository($repository);
		return array(
			self::FILTER => $this->filter,
			self::PAGER  => $this->pager,
			self::SORTER => $this->sorter,
		);
	}
	
	protected function setRepository(ObjectRepository $repository) {
		if ($this->filter) {
			$this->filter->setRepository($repository);
		}
		if ($this->sorter) {
			$this->sorter->setRepository($repository);
		}
		if ($this->pager) {
			$this->pager->setRepository($repository);
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
