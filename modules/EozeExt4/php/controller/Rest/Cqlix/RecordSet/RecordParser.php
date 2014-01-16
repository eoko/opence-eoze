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

namespace eoko\modules\EozeExt4\controller\Rest\Cqlix\RecordSet;

use eoko\modules\EozeExt4\controller\Rest\Cqlix\Record;

/**
 * A parser that maps fields selected in the query to the database to the fields in the record.
 *
 * This class implements {@link FieldValueReader} so that it can be used to read the value of
 * a field (for relations).
 *
 * @since 2013-04-30 11:51
 */
class RecordParser implements FieldValueReader {

	/**
	 * Associative array in which the indexes are the server field names, and the values are the
	 * fully qualified name of the field in input data (i.e. columns in the query's result).
	 *
	 * @var string[]
	 */
	private $fieldsMapping;

	/**
	 * Field value readers indexed by server field names.
	 *
	 * @var FieldValueReader[]
	 */
	private $readers;

	/**
	 * @var \ModelTable
	 */
	private $table;

	/**
	 * @var array|null
	 */
	private $context;

	/**
	 * Name of the field to read in the input for the value of the parent record's identifier. This
	 * field value will be set as the record's {@link Record::setParentId() parent id}.
	 *
	 * This is used only when this parsers is used to parse child records (i.e. from a relation).
	 *
	 * @var string|null
	 */
	private $inputParentIdFieldName = null;

	/**
	 * Creates a new RecordParser object.
	 *
	 * @param \ModelTable $table
	 * @param array $context
	 */
	public function __construct(\ModelTable $table, array $context = null) {
		$this->table = $table;
		$this->context = $context;
	}

	/**
	 * Sets the name of the field to read in the input for the value of the record's parent record's
	 * identifier.
	 *
	 * @param string|null $parentIdFieldName
	 */
	public function setParentIdFieldName($parentIdFieldName) {
		$this->inputParentIdFieldName = $parentIdFieldName;
	}

	/**
	 * Gets the ModelTable this parser is working with.
	 *
	 * @return \ModelTable
	 */
	public function getTable() {
		return $this->table;
	}

	/**
	 * @inheritdoc
	 */
	public function readFieldValue($input) {
		return $this->parseRecord($input);
	}

	/**
	 * Adds a mapping entry for the specified input field to a server field.
	 *
	 * @param string $fqFieldName
	 * @param string $serverFieldName
	 * @return $this
	 */
	public function addMapping($fqFieldName, $serverFieldName) {
		$this->fieldsMapping[$serverFieldName] = $fqFieldName;
		return $this;
	}

	/**
	 * Adds a {@link FieldValueReader} for the field specified by its server name.
	 *
	 * @param string $fieldName
	 * @param FieldValueReader $reader
	 * @return $this
	 */
	public function addFieldValueReader($fieldName, FieldValueReader $reader) {
		$this->readers[$fieldName] = $reader;
		return $this;
	}

	/**
	 * Creates a record from the specified input data. If the input data is empty,
	 * then `null` is returned.
	 *
	 * @param array $input
	 * @return Record|null
	 */
	public function parseRecord($input) {

		$data = $this->parseRecordData($input);

		if ($data) {
			$record = new Record($this->table, $this->context, $data);

			if (isset($this->inputParentIdFieldName)) {
				$record->setParentId($input[$this->inputParentIdFieldName]);
			}

			return $record;
		} else {
			return null;
		}
	}

	/**
	 * Parses input data to a record data object. This method returns `null` if the specified
	 * input data is empty (from the perspective of the configured mapping).
	 *
	 * @param array $input
	 * @return array|null
	 */
	private function parseRecordData($input) {
		$data = array();
		$hasData = false;

		if ($this->fieldsMapping) {
			foreach ($this->fieldsMapping as $name => $mapping) {
				if (isset($input[$mapping])) {
					$hasData = true;
					$data[$name] = $input[$mapping];
				} else {
					$data[$name] = null;
				}
			}
		}

		if ($this->readers) {
			foreach ($this->readers as $name => $parser) {
				$record = $parser->readFieldValue($input);;
				$data[$name] = $record;
				if ($record) {
					$hasData = true;
				}
			}
		}

		return $hasData ? $data : null;
	}

}

