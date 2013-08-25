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

use Model;
use eoko\modules\CqlixHistory\DeltaParser;

/**
 * A stack of {@link DeltaParser delta parsers}, that implements the logic to exclude the
 * fields handled by the first parsers from the subsequent ones, in
 * {@link DeltaParser::getDeltaRecords()}.
 *
 * @category Eoze
 * @package CqlixHistory
 * @subpackage DeltaParser
 * @since 2013-04-03 16:16
 */
class Stack implements DeltaParser {

	/**
	 * @var DeltaParser[]
	 */
	private $parsers;

	/**
	 * Cache for parsed field names.
	 *
	 * @var string[]
	 */
	private $parsedFieldNames = null;

	/**
	 * Creates a new {@link DeltaParser delta parser} stack.
	 *
	 * @param DeltaParser[] $parsers
	 */
	public function __construct(array $parsers) {
		$this->parsers = $parsers;
	}

	/**
	 * @inheritdoc
	 */
	public function getDeltaRecords(array $originalValues, Model $modifiedModel, array $excludedFieldsMap = null) {

		$records = array();

		foreach ($this->parsers as $parser) {

			// Get the delta records
			$newRecords = $parser->getDeltaRecords($originalValues, $modifiedModel, $excludedFieldsMap);

			if ($newRecords) {
				// Store the new records in the returned array
				$records = array_merge($records, $newRecords);

				// Update the black list
				foreach ($newRecords as $record) {
					$excludedFieldsMap[$record->getModelField()] = true;
				}
			}
		}

		return $records;
	}

	/**
	 * @inheritdoc
	 */
	public function readValues(Model $model, array $fields = null) {
		$values = array();

		foreach ($this->parsers as $parser) {
			$values += $parser->readValues($model, $fields);
		}

		return $values;
	}

	/**
	 * @inheritdoc
	 */
	public function getTrackedFieldNames() {
		if ($this->parsedFieldNames === null) {
			$map = array();

			foreach ($this->parsers as $parser) {
				foreach ($parser->getTrackedFieldNames() as $field) {
					$map[$field] = true;
				}
			}

			$this->parsedFieldNames = array_keys($map);
		}
		return $this->parsedFieldNames;
	}
}
