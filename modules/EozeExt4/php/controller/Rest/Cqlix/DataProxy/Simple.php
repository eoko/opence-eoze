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

use Model as Record;
use ModelTable;
use Zend\Filter\Word\UnderscoreToCamelCase;
use eoko\modules\EozeExt4\controller\Rest\Cqlix\DataProxy as DataProxyInterface;

/**
 * Basic implementation of a {@link eoko\modules\EozeExt4\controller\DataProxy}.
 *
 * @since 2013-04-18 10:49
 */
class Simple implements DataProxyInterface {

	/**
	 * @var ModelTable
	 */
	private $table;

	/**
	 * Creates a new proxy instance.
	 *
	 * @param ModelTable $table
	 */
	public function __construct(ModelTable $table) {
		$this->table = $table;
	}

	/**
	 * Returns the table this proxy works with.
	 *
	 * @return ModelTable
	 */
	protected function getTable() {
		return $this->table;
	}

	protected function createContext() {
		return null;
	}

	protected function createReadContext() {
		return $this->createContext();
	}

	protected function createWriteContext() {
		return $this->createContext();
	}

	/**
	 * @inheritdoc
	 */
	public function createRecord(array $data = null, array $context = null) {
		if ($context === null) {
			$context = $this->createWriteContext();
		}
		$record = $this->table->createModel($data, $context);
		return $record;
	}

	/**
	 * @inheritdoc
	 */
	public function loadRecord($id) {
		$context = $this->createReadContext();
		$table = $this->getTable();
		$record = $table->loadModel($id, $context);
		return $record;
	}

	/**
	 * @inheritdoc
	 */
	public function getRecordData(Record $record) {
		$filter = new UnderscoreToCamelCase();

		$data = array();
		foreach ($this->doGetRecordData($record) as $field => $value) {
			$name = lcfirst($filter($field));
			$data[$name] = $value;
		}

		return $data;
	}

	/**
	 * Convenience method for overriding {@link getRecordData()}, with the possibility of
	 * specializing the record type (and not worrying about what is done in `getRecordData`).
	 *
	 * @param Record $record
	 * @return Array
	 */
	protected function doGetRecordData(Record $record) {
		return $record->getData();
	}

	/**
	 * @inheritdoc
	 */
	public function setRecordData(Record $record, array $inputData) {
		$this->doSetRecordData($record, $inputData);
	}

	/**
	 * Convenience method for overriding {@link setRecordData()}, with the possibility of
	 * specializing the record type (and not worrying about what is done in `setRecordData`).
	 *
	 * @param Record $record
	 * @param array $inputData
	 */
	protected function doSetRecordData(Record $record, array $inputData) {
		$record->setFields($inputData);
		foreach ($inputData as $field => $value) {
			$method = 'set' . ucfirst($field);
			$record->$method($value);
		}
	}
}
