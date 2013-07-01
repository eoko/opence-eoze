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

namespace eoko\modules\EozeExt4\controller\Rest;

use Model as Record;
use ModelTable;
use eoko\modules\EozeExt4\controller\Rest;
use eoko\modules\EozeExt4\controller\DatabaseAdapter\Pdo as PdoDatabaseAdapter;
use eoko\modules\EozeExt4\controller\DatabaseAdapter;

/**
 * Base implementation for a {@link eoko\modules\EozeExt4\controller\Rest RESTFul executor}
 * bound to a Cqlix model.
 *
 * @since 2013-04-18 10:27
 */
abstract class Cqlix extends Rest {

	/**
	 * @var DatabaseAdapter
	 */
	private $databaseAdapter;

	/**
	 * @var Cqlix\DataProxy
	 */
	private $dataProxy;

	/**
	 * @return ModelTable
	 */
	abstract protected function getTable();

	/**
	 * @inheritdoc
	 */
	protected function getDatabaseAdapter() {
		if (!$this->databaseAdapter) {
			$this->databaseAdapter = $this->createDatabaseAdapter();
		}
		return $this->databaseAdapter;
	}

	/**
	 * Creates the database adapter.
	 *
	 * @return PdoDatabaseAdapter
	 */
	protected function createDatabaseAdapter() {
		$pdo = $this->getTable()
			->getDatabase()
			->getConnection();
		return new PdoDatabaseAdapter($pdo);
	}

	/**
	 * Gets the request params.
	 *
	 * @return Cqlix\Request\Params
	 */
	protected function getRequestParams() {
		$request = $this->getRequest();
		return new Cqlix\Request\Params($request, $this->getCrudOperation());
	}

	/**
	 * @param bool|array $defaultExpand
	 * @return Cqlix\DataProxy
	 */
	private function getDataProxy($defaultExpand) {
		if (!$this->dataProxy) {
			$requestParams = $this->getRequestParams();

			// Get from implem
			$proxy = $this->doGetDataProxy();
			// Configure expand params
			$expandParam = $requestParams->get($requestParams::EXPAND, null);
			$proxy->setExpandedParams($expandParam, $defaultExpand);
			// Store
			$this->dataProxy = $proxy;
		}
		// Return
		return $this->dataProxy;
	}

	/**
	 * @return Cqlix\DataProxy
	 */
	abstract protected function doGetDataProxy();

	/**
	 * @inheritdoc
	 */
	public function getRecord($id = null) {

		$requestParams = $this->getRequestParams();
		$response = $this->getResponse();

		$dataProxy = $this->getDataProxy(true);

		// --- Parse request params

		if ($id === null) {
			$id = $requestParams->req($requestParams::ID);
		}

		// --- Load record

		$model = $dataProxy->loadRecord($id, $requestParams);

		if (!$model) {
			$response->setStatusCode($response::STATUS_CODE_404);
			return $response;
		}

		// --- Format data

		$record = Rest\Cqlix\Record::fromModel($model);

		$recordData = $dataProxy->getRecordData($record);
		$metaData = array(
			'$expanded' => $dataProxy->getResponseExpanded(),
		);

		$data = array_merge($metaData, $recordData);

		// --- Return

		$this->set(array(
			'expand' => $dataProxy->getResponseExpandable(),
			'expanded' => $dataProxy->getResponseExpanded(),
			'queries' => \Query::getExecutionCount(),
			'data' => $data,
		));

		return true;
	}

	/**
	 * @inheritdoc
	 *
	 * Creates a new record.
	 */
	public function postRecord($inputData = null) {

		$requestParams = $this->getRequestParams();
		$response = $this->getResponse();

		// --- Parse request params

		if ($inputData === null) {
			$inputData = $requestParams->req($requestParams::DATA);
		}

		if (isset($inputData['$expanded'])) {
			$dataProxy = $this->getDataProxy($inputData['$expanded']);
		} else {
			$dataProxy = $this->getDataProxy(true);
		}

		// --- Create record

		$record = $dataProxy->createRecord(null, $requestParams);

		if (!$record) {
			$response->setStatusCode($response::STATUS_CODE_500);
			$this->set('errorCause', 'Could not create record.');
			return false;
		}


		// --- Update, save & return

		return $this->updateAndSaveRecord($record, $inputData, true);
	}

	public function putRecords($data) {
		$proxy = $this->getDataProxy(true);

		foreach ($data as $record) {
			$id = $proxy->getIdFromData($record);
			if (true !== $response = $this->putRecord($id, $record)) {
				return $response;
			}
		}

		return true;
	}

	/**
	 * @inheritdoc
	 *
	 * Updates an existing record.
	 */
	public function putRecord($id = null, $inputData = null) {

		$requestParams = $this->getRequestParams();
		$response = $this->getResponse();

		// --- Parse request params

		if ($id === null) {
			$id = $requestParams->req($requestParams::ID);
		}

		if ($inputData === null) {
			$inputData = $requestParams->req($requestParams::DATA);
		}

		if (isset($inputData['$expanded'])) {
			$dataProxy = $this->getDataProxy($inputData['$expanded']);
		} else {
			$dataProxy = $this->getDataProxy(true);
		}

		// --- Load record

		$record = $dataProxy->loadRecord($id, $requestParams);

		if (!$record) {
			$response->setStatusCode($response::STATUS_CODE_404);
			return $response;
		}

		// --- Update, save & return

		return $this->updateAndSaveRecord($record, $inputData, false);
	}

	/**
	 * Updates the data of the passed {@link Record model}, and save it in the database.
	 *
	 * @param \Model $record
	 * @param array $inputData
	 * @param bool $newRecord
	 * @return bool|\Zend\Http\Response
	 */
	private function updateAndSaveRecord(Record $record, array $inputData, $newRecord) {

		$databaseAdapter = $this->getDatabaseAdapter();
		$dataProxy = $this->getDataProxy(true);

		$response = $this->getResponse();

		// --- Update record

		try {
			$dataProxy->setRecordData($record, $inputData);
		} catch (\Exception $ex) {

			$this->logException($ex);

			$response->setStatusCode($response::STATUS_CODE_400);

			return $response;
		}

		// --- Save

		try {
			$databaseAdapter->beginTransaction();
			$record->save($newRecord);
			$databaseAdapter->commitTransaction();
		} catch (\Exception $ex) {

			$databaseAdapter->rollbackTransaction();

			$this->logException($ex);

			$response->setStatusCode($response::STATUS_CODE_500);

			return $response;
		}

		// --- Load updated data

		$updatedModel = $record->getDatabaseCopy();
		$updatedRecord = Rest\Cqlix\Record::fromModel($updatedModel);
		$updatedData = $dataProxy->getRecordData($updatedRecord);

		// --- Return

		$this->set(array(
			'queries' => \Query::getExecutionCount(),
			'data' => $updatedData,
		));

		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function deleteRecord($id) {
		try {
			$ids = is_array($id) ? $id : array($id);
			$this->getTable()->deleteWherePkIn($ids);
			return true;
		} catch (\UserException $ex) {
			$response = $this->getResponse();
			$response->setContent($ex->getUserMessage());
			$response->setStatusCode($response::STATUS_CODE_409);
			return $response;
		} catch (\Exception $ex) {
			$response = $this->getResponse();
			$response->setStatusCode($response::STATUS_CODE_500);
			return $response;
		}
	}

	/**
	 * @inheritdoc
	 */
	public function deleteRecords($data) {
		$proxy = $this->getDataProxy(false);

		$ids = array();

		foreach ($data as $record) {
			if (is_array($record)) {
				$ids[] = $proxy->getIdFromData($record);
			} else {
				$ids[] = $record;
			}
		}

		return $this->deleteRecord($ids);
	}

	/**
	 * @inheritdoc
	 */
	public function getList() {

		$requestParams = $this->getRequestParams();

		$dataProxy = $this->getDataProxy(false);

		try {
			/** @var $records Rest\Cqlix\RecordSet */
			$records = $dataProxy->createRequestRecords($requestParams);
		} catch (Rest\Cqlix\Exception\UnknownField $ex) {
			$response = $this->getResponse();
			$response->setStatusCode($response::STATUS_CODE_400);
			return $response;
		} catch (\UnsupportedOperationException $ex) {
			$response = $this->getResponse();
			$response->setStatusCode($response::STATUS_CODE_501);
			return $response;
		}

		$data = array();
		foreach ($records as $record) {
			$data[] = $dataProxy->getRecordData($record);
		}

		if ($requestParams->has($requestParams::CONFIGURE)) {
			$this->set('metaData', $dataProxy->getMetaData());
		}

		// Data
		$responseData = array(
			'queries' => \Query::getExecutionCount(),

			$requestParams->getParamName($requestParams::EXPAND) => $dataProxy->getResponseExpandable(),
			$requestParams->getParamName($requestParams::EXPANDED) => $dataProxy->getResponseExpanded(),
		);

		foreach ($records->getResponseMetaData($requestParams) as $name => $value) {
			$responseData[$name] = $value;
		}

		$this->set($responseData);

		$this->set('data', $data);

		return true;
	}

	protected function getLastModified() {
//		dump($this->getTable()->getLastModified());
		return $this->getTable()->getLastModified();
		return new \DateTime('2013-01-26 23:54:03');
	}
}
