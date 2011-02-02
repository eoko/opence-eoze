<?php

class DataStore {

	public $rows;
	public $count;

	protected $initialRows = null;
	protected $initialCount = null;

	public $requestParams = array(
		'start' => 'start',
		'limit' => 'limit',
		'sort' => 'sort',
		'sortDir' => 'dir',
		'query' => 'query',
	);

	public $dataNodeName = 'data', $countNodeName = 'count';

	protected $sortOrder = null;
	protected $defaultSortOrder = null;
	protected $defaultSearchFields = null;
	protected $sortAliases = null;
	protected $pageSize = null;

	/** @var ModelTableQuery */
	protected $query = null;
	protected $queryExecutor = null;
	/** @var Closure */
	protected $rowConverter = null;

	protected function __construct() {}
//	protected function __construct(ModelTableQuery $query, $rowConverter = null) {
//		$this->query = $query;
//		$this->rowConverter = $rowConverter;
//		// Load rows
//		$this->forceReload();
//	}
////	public function __construct($loader) {
////		if ($loader instanceof Query) {
////			$this->rows = $loader->executeSelect();
////		} else if (is_array($loader)) {
////			$this->rows = $loader;
////		} else {
////			throw new IllegalArgumentException('$loader must be either a Query or an array');
////		}
////		$this->count = count($this->rows);
////	}

	public function forceReload() {
		if (null !== $this->queryExecutor) {
			if (null !== $rowConverter = $this->rowConverter) {
				$this->rows = array();
				foreach ($this->queryExecutor->execute() as $row) {
					$this->rows[] = $rowConverter->convert($row);
				}
			} else {
				$this->rows = $this->queryExecutor->execute();
			}
			$this->count = count($this->rows);
		} else if ($this->initialRows !== null && $this->initialCount !== null) {
			$this->rows = $this->initialRows;
			$this->count = $this->initialCount;
		} else {
			throw new IllegalStateException('Missing initial rows data');
		}
	}

	/**
	 *
	 * @param ModelTableQuery $query
	 * @param callback $rowConverterCallback
	 * @return DataStore
	 */
	public static function fromQuery(ModelTableQuery $query, $rowConverterCallback = null) {
		$instance = new DataStore();
		$instance->queryExecutor = $query->createExecutor();
		$instance->rowConverter = $rowConverterCallback;
		// Load rows
		$instance->forceReload();
		return $instance;
	}

	/**
	 *
	 * @param array $rows
	 * @return DataStore
	 */
	public static function fromArray(array $rows) {
		$instance = new DataStore();
		$instance->initialRows = $instance->rows = $rows;
		$instance->initialCount = $instance->count = count($rows);
		return $instance;
	}

	public function count() {
		return $this->count;
	}

	public function executeRequest(Request $request) {
		$this->sortFromRequest($request);
		$this->filterFromRequest($request);
		$this->putInResponseFromRequest($request);
	}

	public function sortFromRequest(Request $request) {
		if (null !== $sort = $request->get($this->requestParams['sort'], null)) {
			if (is_array($sort)) {
				if ($request->has($this->requestParams['sortDir'])) {
					Logger::get($this)->warn(
						$this->requestParams['sortDir'] . ' request param ignored'
					);
				}
				$this->orderBy($sort);
			} else {
				$this->orderBy($sort, $request->get('dir', 'ASC'));
			}
		}
	}

	public function filterFromRequest(Request $request) {
		if (null !== $q = $request->get($this->requestParams['query'], null)) {
			$q = '/' . preg_quote($q, '/') . '/';
			$this->rows = $this->searchOr($q, $this->defaultSearchFields);
			$this->count = count($this->rows);
		}
	}

	/**
	 * Put data from store in the response, according to the limit options set
	 * in the request.
	 * @param Request $request
	 */
	public function putInResponseFromRequest(Request $request) {
		if (null !== $limit = $request->get($this->requestParams['limit'], null)) {
			$this->putSliceInResponse(
				$limit,
				$request->get($this->requestParams['start'], 0)
			);
		} else {
			$this->putInResponse();
		}
	}

	private static function convertDir(&$dir) {
		switch ($dir) {
			default: case 'asc': case 'ASC': return $dir = 1;
			case 'desc': case 'DESC': return $dir = -1;
		}
	}

	/**
	 *
	 * @param mixed $alias	an alias String or an array of Alias in the form
	 * array($aliasName => {$fieldName|array($fieldName1[,$fieldName2[,...]])}
	 * @param mixed $field  the name of the actual field to use for the alias,
	 * or an Array of the form array($fieldName1[,$fieldName2[,...]]). If the
	 * $alias param is specified as an array, then this parameter is ignored,
	 * and setting it will trigger an IllegalArgumentException.
	 * @return DataStore
	 */
	public function putSortAlias($alias, $field = null) {
		if (is_array($alias)) {
			if ($field !== null) throw new IllegalArgumentException();
			foreach ($alias as $a => $f) $this->sortAliases[$a] = $f;
		} else {
			$this->sortAliases[$alias] = $field;
		}
		return $this;
	}

	public function orderBy($field, $dir = null) {
		if ($this->makeSortOrder($this->sortOrder, $field, $dir)) {
			$this->updateSort();
		}
	}

	public function updateSort() {
		usort($this->rows, array($this, 'sortCmp'));
	}

	public function setDefaultSearchFields($fields) {
		if (is_array($fields)) {
			$this->defaultSearchFields = $fields;
		} else {
			$this->defaultSearchFields = array($fields);
		}
	}

	/**
	 * @param string|array $field
	 * @param string|array $dir
	 * @return DataStore 
	 */
	public function setDefaultSortOrder($field, $dir = null) {
		$this->makeSortOrder($this->defaultSortOrder, $field, $dir);
		return $this;
	}

	private function makeSortOrder(&$on, $field_s, $dir = null) {
		$sortOrder = array();
		if (is_array($field_s)) {
			if ($dir !== null) throw new IllegalArgumentException('$dir param ignored when $field is an array');

			foreach ($field_s as $field => $dir) {
				$dir = self::convertDir($dir);
				if (isset($this->sortAliases[$field])) {
					if (is_array($this->sortAliases[$field])) {
						foreach ($this->sortAliases[$field] as $field) {
							$sortOrder[$field] = $dir;
						}
					} else {
						$sortOrder[$this->sortAliases[$field]] = $dir;
					}
				}
			}
		} else {
			$dir = self::convertDir($dir);
			if (isset($this->sortAliases[$field_s])) {
				if (is_array($this->sortAliases[$field_s])) {
					foreach ($this->sortAliases[$field_s] as $field) {
						$sortOrder[$field] = $dir;
					}
				} else {
					$sortOrder[$this->sortAliases[$field_s]] = $dir;
				}
			}
		}

		// Check for change
		if (!ArrayHelper::compareAssoc($sortOrder, $on)) {
			$on = $sortOrder;
			return true;
		} else {
			return false;
		}
	}

	public function sortCmp($a, $b) {
		$sortOrder = array();
		$default = $this->defaultSortOrder;
		foreach ($this->sortOrder as $col => $dir) {
			$sortOrder[$col] = $dir;
			unset($default[$col]);
		}
		if ($default !== null) {
			foreach ($default as $col => $dir) {
				$sortOrder[$col] = $dir;
			}
		}
		foreach ($sortOrder as $col => $dir) {
			if (0 !== $r = strnatcmp($a[$col], $b[$col])) return $r * $dir;
		}
		return 0;
	}
	
	const CMP_BINARY = '===';
	const CMP_BINARY_NOT = '!==';
	const CMP_NAT	 = '==';
	const CMP_NAT_NOT	 = '!=';
	const CMP_REGEX  = 'REGEX';

	public function getSubsetFilteredBy($field, $value, $mode = self::CMP_BINARY) {
		$r = array();
		switch ($mode) {
			case self::CMP_BINARY:
				foreach ($this->rows as $row) {
					if ($row[$field] === $value) $r[] = $row;
				}
				break;
			case self::CMP_BINARY_NOT:
				foreach ($this->rows as $row) {
					if ($row[$field] !== $value) $r[] = $row;
				}
				break;

			case self::CMP_NAT:
				foreach ($this->rows as $row) {
					if ($row[$field] == $value) $r[] = $row;
				}
				break;
			case self::CMP_NAT_NOT:
				foreach ($this->rows as $row) {
					if ($row[$field] != $value) $r[] = $row;
				}
				break;

			case self::REGEX:
				foreach ($this->rows as $row) {
					if (preg_match($value, $row[$field])) $r[] = $row;
				}
				break;

			default: throw new IllegalArgumentException();
		}
		return $r;
	}

	public function getSubset($start, $length = null) {
		return array_slice($this->rows, $start, $length);
	}

	public function setPageSize($len) {
		$this->pageSize = $len;
	}

	public function getPage($i) {
		if ($this->pageSize === null) throw new IllegalStateException(
			'Calcullating pages requires $limitLength to be set'
		);
		return $this->getSubset($i*$this->pageSize, $this->pageSize);
	}

	public function putInResponse() {
		ExtJSResponse::put($this->countNodeName, $this->count);
		ExtJSResponse::put($this->dataNodeName, $this->rows);
	}

	public function putPageInResponse($iPage) {
		ExtJSResponse::put($this->countNodeName, $this->count);
		ExtJSResponse::put($this->dataNodeName, $this->getPage($iPage));
	}

	public function putSliceInResponse($limit, $start = 0) {
		ExtJSResponse::put($this->countNodeName, $this->count);
		ExtJSResponse::put($this->dataNodeName, $this->getSubset($start, $limit));
	}

	public function searchOr($regex, $fields = null) {
		$r = array();
		foreach ($this->rows as $row) {
			if ($fields === null) {
				foreach ($row as $field) {
					if (preg_match($regex, $field)) {
						$r[] = $row;
						break;
					}
				}
			} else if (is_array($fields)) {
				foreach ($fields as $field) {
					if (preg_match($regex, $row[$field])) {
						$r[] = $row;
						break;
					}
				}
			} else {
				if (preg_match($regex, $row[$fields])) $r[] = $row;
			}
		}
		return $r;
	}
}