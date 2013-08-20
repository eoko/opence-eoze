<?php
/**
 * Copyright (C) 2013 Eoko
 *
 * This file is part of Opence.
 *
 * Opence is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Opence is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Opence. If not, see <http://www.gnu.org/licenses/gpl.txt>.
 *
 * @copyright Copyright (C) 2013 Eoko
 * @licence http://www.gnu.org/licenses/gpl.txt GPLv3
 * @author Éric Ortega <eric@eoko.fr>
 */

namespace eoko\modules\EozeExt4\controller\Rest\Cqlix\DataProxy;

use eoko\modules\EozeExt4\controller\Rest\Cqlix\Query\Processor as QueryProcessor;
use eoko\modules\EozeExt4\controller\Rest\Cqlix\Request\Params as RequestParams;
use eoko\modules\EozeExt4\controller\Rest;
use ModelTable;
use Model;
use Query;
use Request;
use eoko\modules\EozeExt4\controller\Rest\Cqlix\DataProxy as DataProxyInterface;
use eoko\modules\EozeExt4\Exception;
use eoko\modules\EozeExt4\controller\Rest\Cqlix\RecordSet;

/**
 * Default implementation for {@link DataProxyInterface}.
 *
 * Data contexts
 * -------------
 *
 * Contexts for data queries, model loading, saving, etc., are created by these three methods:
 *
 * - {@link AbstractProxy::createContext()}
 * - {@link AbstractProxy::createReadContext()}
 * - {@link AbstractProxy::createWriteContext()}
 *
 * If specific contexts (i.e. read or write) are not overridden, then will simply call the createContext
 * method.
 *
 * @since 2013-04-25 12:19
 */
abstract class AbstractProxy implements DataProxyInterface, HasQueryContext {

	public function createQueryContext(RequestParams $request) {
		return $this->createContext($request);
	}

	/**
	 * Creates data context, depending on the CRUD operation (read from the {@link RequestParams}).
	 *
	 * @see AbstractProxy::createCreateContext()
	 * @see AbstractProxy::createReadContext()
	 * @see AbstractProxy::createUpdateContext()
	 * @see AbstractProxy::createDeleteContext()
	 * @see AbstractProxy::createWriteContext()
	 *
	 * @param RequestParams $requestParams
	 * @return array|null
	 */
	protected function createContext(RequestParams $requestParams) {
		switch ($requestParams->getCrudOperation()) {
			case Rest::OPERATION_CREATE:
				return $this->createCreateContext($requestParams);
			case Rest::OPERATION_READ:
				return $this->createReadContext($requestParams);
			case Rest::OPERATION_UPDATE:
				return $this->createUpdateContext($requestParams);
			case Rest::OPERATION_DELETE:
				return $this->createDeleteContext($requestParams);
			default:
				return null;
		}
	}

	/**
	 * Creates data context for creation of new records.
	 *
	 * @param RequestParams $requestParams
	 * @return null
	 */
	protected function createCreateContext(RequestParams $requestParams) {
		return null;
	}

	/**
	 * Creates data context for reading from existing records.
	 *
	 * @param RequestParams $requestParams
	 * @return array|null
	 */
	protected function createReadContext(RequestParams $requestParams) {
		return null;
	}

	/**
	 * Creates data context for writing.
	 *
	 * This is the default implementation for both {@link AbstractProxy::createUpdateContext()},
	 * and {@link AbstractProxy::createDeleteContext()}.
	 *
	 * @param RequestParams $requestParams
	 * @return array|null
	 */
	protected function createWriteContext(RequestParams $requestParams) {
		return null;
	}

	/**
	 * Creates data context for modification of existing records.
	 *
	 * Default implementation delegates to {@link AbstractProxy::createWriteContext()}.
	 *
	 * @param RequestParams $requestParams
	 * @return array|null
	 */
	protected function createUpdateContext(RequestParams $requestParams) {
		return $this->createWriteContext($requestParams);
	}

	/**
	 * Creates data context for deletion of existing records.
	 *
	 * Default implementation delegates to {@link AbstractProxy::createWriteContext()}.
	 *
	 * @param RequestParams $requestParams
	 * @return array|null
	 */
	protected function createDeleteContext(RequestParams $requestParams) {
		return $this->createWriteContext($requestParams);
	}

	/**
	 * @inheritdoc
	 */
	public function createRecord(array $data = null, RequestParams $requestParams) {
		$context = $this->createContext($requestParams);
		$table = $this->getTable();
		$record = $table->createModel($data, false, $context);
		return $record;
	}

	/**
	 * @inheritdoc
	 */
	public function loadRecord($id, RequestParams $requestParams) {
		$context = $this->createContext($requestParams);
		$table = $this->getTable();
		$record = $table->loadModel($id, $context);
		return $record;
	}

	/**
	 * @inheritdoc
	 */
	public function setRecordData(Model $record, array $inputData) {
		$this->doSetRecordData($record, $inputData);
	}

	/**
	 * Convenience method for overriding {@link setRecordData()}, with the possibility of
	 * specializing the record type (and not worrying about what is done in `setRecordData`).
	 *
	 * @param Model $record
	 * @param array $inputData
	 */
	abstract protected function doSetRecordData(Model $record, array $inputData);

	/**
	 * @inheritdoc
	 */
	public function createRequestRecords(RequestParams $requestParams) {

		// --- Query

		// create
		$query = $this->createListQuery($requestParams);

		// select proxy's fields
		$parser = $this->selectListFields($query, '', $requestParams);

		// --- Query processors

		// create
		$processors = $this->createListQueryProcessors($requestParams);

		// apply
		foreach ($processors as $processor) {
			// to the query
			$processor->apply($query, $this);
		}

		// --- Create & return the record set

		return new RecordSet\OnePass($parser, $query, $processors);
	}

	/**
	 * Creates the query that will be used to load the records of the list defined
	 * by the passed request params.
	 *
	 * @param RequestParams $requestParams
	 * @return \ModelTableQuery
	 */
	protected function createListQuery(RequestParams $requestParams) {

		$table = $this->getTable();

		$context = $this->createContext($requestParams);
		$query = $table->createQuery($context);

		// A minima, we want to select the identifier (to be able to resolve
		// relation links)
		$query->select($table->getPrimaryKeyName());

		return $query;
	}

	/**
	 * Creates the query processors to apply to the list query from the supplied request params.
	 *
	 * @param RequestParams $requestParams
	 * @return QueryProcessor[]
	 */
	protected function createListQueryProcessors(RequestParams $requestParams) {

		$processors = array();

		// --- Ids

		$ids = $requestParams->get($requestParams::IDS, null);

		if ($ids !== null) {
			$processors[] = new QueryProcessor\Ids($this, $ids);
		}

		// --- Limit

		$limit = $requestParams->get($requestParams::LIMIT, 25);
		$start = $requestParams->get($requestParams::START, 0);

		if ($limit !== false && $limit !== 'false') {

			$limitData = array(
				'limit' => (int) $limit,
				'start' => (int) $start,
			);

			$processors[] = new QueryProcessor\Limit($this, $limitData);
		} else {
			$processors[] = new QueryProcessor\NoLimit($this);
		}

		// --- Sort

		$sort = $requestParams->get($requestParams::SORT, null);

		if ($sort !== null) {
			if (is_string($sort)) {
				$sort = json_decode($sort, true);
			}
			$processors[] = new QueryProcessor\Sort($this, $sort);
		}

		// --- Filters

		$filters = $requestParams->get($requestParams::FILTERS, null);

		if ($filters) {
			if (is_string($filters)) {
				$filters = json_decode($filters, true);
			}
			$processors[] = new QueryProcessor\Filters($this, $filters);
		}

		// --- Return

		return $processors;
	}
}
