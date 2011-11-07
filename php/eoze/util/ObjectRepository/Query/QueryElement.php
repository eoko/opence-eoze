<?php

namespace eoze\util\ObjectRepository\Query;

use eoze\util\ObjectRepository\Query;
use eoze\util\ObjectRepository;

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
	 * @var ObjectRepository
	 */
	private $repository;
	
	public static function getClass() {
		return get_called_class();
	}
	
	/**
	 * @return ObjectRepository
	 */
	protected function getRepository() {
		if (!$this->repository) {
			throw new IllegalStateException('Repository has not been set');
		}
		return $this->repository;
	}
	
	public function setRepository(ObjectRepository $repository) {
		$this->repository = $repository;
	}
	
//	public function options(ObjectRepository $repository, &$filters, &$sorter, &$pager) {
//		$this->repository = $repository;
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
	
	public function getOptions(ObjectRepository $repository) {
		$this->repository = $repository;
		return array($this->type => $this);
	}
}
