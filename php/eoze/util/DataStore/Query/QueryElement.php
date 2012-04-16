<?php

namespace eoze\util\DataStore\Query;

use eoze\util\DataStore\Query;
use eoze\util\DataStore;

use IllegalStateException;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 2 nov. 2011
 */
abstract class QueryElement implements Query {
	
	/**
	 * Abstract type.
	 * @var string
	 */
	protected $type;

	/**
	 * @var DataStore
	 */
	private $store;
	
	public static function getClass() {
		return get_called_class();
	}
	
	/**
	 * @return DataStore
	 */
	protected function getRepository() {
		if (!$this->repository) {
			throw new IllegalStateException('Repository has not been set');
		}
		return $this->repository;
	}
	
	public function setRepository(DataStore $store) {
		$this->repository = $store;
	}
	
//	public function options(DataStore $store, &$filters, &$sorter, &$pager) {
//		$this->repository = $store;
//		if ($this instanceof Filter) {
//			$filters = array($this);
//		} else if ($this instanceof Sorter) {
//			$sorter = $this;
//		} else if ($this instanceof Pager) {
//			$pager = $this;
//		} else {
//			throw new IllegalStateException();
//		}
//	}
	
	public function getOptions(DataStore $store) {
		$this->repository = $store;
		return array($this->type => $this);
	}
}
