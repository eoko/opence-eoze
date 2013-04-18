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
	 * @return Cqlix\DataProxy
	 */
	protected function getDataProxy() {
		if (!$this->dataProxy) {
			$this->dataProxy = $this->createDataProxy();
		}
		return $this->dataProxy;
	}

	/**
	 * @return Cqlix\DataProxy
	 */
	protected function createDataProxy() {
		$table = $this->getTable();
		return new Cqlix\DataProxy\Simple($table);
	}

	/**
	 * @inheritdoc
	 */
	public function getRecord($id = null) {

		$request = $this->getRequest();
		$response = $this->getResponse();

		$dataProxy = $this->getDataProxy();

		// --- Parse request params

		if ($id === null) {
			$id = $request->req('id');
		}

		// --- Load record

		$record = $dataProxy->loadRecord($id);

		if (!$record) {
			$response->setStatusCode($response::STATUS_CODE_404);
			return $response;
		}

		// --- Format data

		$data = $dataProxy->getRecordData($record);

		// --- Return

		$this->set('data', $data);

		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function postRecord($id = null, $inputData = null) {

		$request = $this->getRequest();
		$response = $this->getResponse();

		$databaseAdapter = $this->getDatabaseAdapter();
		$dataProxy = $this->getDataProxy();

		// --- Parse request params

		if ($id === null) {
			$id = $request->req('id');
		}

		if ($inputData === null) {
			$inputData = $request->req('data');
		}

		// --- Load record

		$record = $dataProxy->loadRecord($id);

		if (!$record) {
			$response->setStatusCode($response::STATUS_CODE_404);
			return $response;
		}

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
			$record->save();
			$databaseAdapter->commitTransaction();
		} catch (\Exception $ex) {
			$this->logException($ex);
			$databaseAdapter->rollbackTransaction();
			$response->setStatusCode($response::STATUS_CODE_500);
			return $response;
		}

		// --- Load updated data

		$updatedRecord = $dataProxy->loadRecord($record->getPrimaryKeyValue());
		$updatedData = $dataProxy->getRecordData($updatedRecord);

		// --- Return

		$this->set('data', $updatedData);

		return true;
	}
}
