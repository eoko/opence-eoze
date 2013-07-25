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

namespace eoko\modules\CqlixHistory\DeltaParser;

use HistoryEntryDelta;
use eoko\modules\CqlixHistory\DeltaParser;
use eoko\modules\CqlixHistory\DeltaRecordInterface;
use Model;
use ModelTable;

/**
 * Base implementation for delta parsers.
 *
 * @category Eoze
 * @package CqlixHistory
 * @subpackage DeltaParser
 * @since 2013-04-03 11:25
 */
abstract class AbstractParser implements DeltaParser {

	/**
	 * @var ModelTable
	 */
	private $table;

	public function __construct($config = null) {
		$this->config = $config;

		// Apply config
		if ($config) {
			foreach ($config as $key => $value) {
				$method = 'set' . ucfirst($key);
				if (method_exists($this, $method)) {
					$this->$method($value);
				}
			}
		}
	}

	/**
	 * Creates a parser with the specified config, and the config that can be inferred
	 * from the table.
	 *
	 * @param $config
	 * @param ModelTable $table
	 * @return AbstractParser
	 */
	public static function createForTable(ModelTable $table, $config = null) {
		/** @var $parser AbstractParser */
		$parser = new static($config);
		$parser->setTable($table);
		return $parser;
	}

	/**
	 * Configure the source model table.
	 *
	 * @param ModelTable $table
	 * @return $this
	 */
	public function setTable(ModelTable $table) {
		$this->table = $table;
		return $this;
	}

	/**
	 * @return ModelTable
	 */
	protected function getTable() {
		return $this->table;
	}

	/**
	 * @inheritdoc
	 */
	final public function getDeltaRecords(array $originalValues, Model $modifiedModel, array $excludedFieldsMap = null) {

		$this->checkIntegrity();

		// Discover the tracked fields that have not been excluded yet
		$whiteList = array();
		foreach ($this->getTrackedFieldNames() as $field) {
			if (!isset($excludedFieldsMap[$field])) {
				$whiteList[] = $field;
			}
		}

		// No field to parse, nothing to do...
		if (!$whiteList) {
			return array();
		}

		// Get the delta records
		$records = $this->doGetDeltaRecords($originalValues, $modifiedModel, $whiteList);

		// Ensure we return an array (even if doGetDeltaRecords returned nothing)
		return $records ? $records : array();
	}

	/**
	 * Checks that the configuration is valid, and throws an exception if it is not the case.
	 * Child classes should add their own tests.
	 *
	 * @throws \DomainException
	 */
	protected function checkIntegrity() {
		if (!$this->table) {
			throw new \DomainException('Invalid configuration: missing model table.');
		}
	}

	/**
	 * @param array $originalValues
	 * @param Model $modifiedModel
	 * @param array $fields
	 * @return array|null
	 */
	abstract protected function doGetDeltaRecords(array $originalValues, Model $modifiedModel, array $fields);

	/**
	 * @param array $values
	 * @return DeltaRecordInterface
	 */
	protected function createDeltaRecord(array $values = array()) {
		return HistoryEntryDelta::create($values);
	}
}
