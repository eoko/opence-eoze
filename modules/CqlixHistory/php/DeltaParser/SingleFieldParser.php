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
use ModelColumn;
use ModelField;
use eoko\modules\CqlixHistory\DeltaRecordInterface;
use eoko\modules\CqlixHistory\Exception;

/**
 * Parser for a single model field.
 *
 * @category Eoze
 * @package CqlixHistory
 * @subpackage DeltaParser
 * @since 2013-04-03 14:58
 */
class SingleFieldParser extends AbstractParser {

	/**
	 * @var string
	 */
	private $fieldName;

	/**
	 * @inheritdoc
	 */
	public function getTrackedFieldNames() {
		return array($this->getFieldName());
	}

	/**
	 * @inheritdoc
	 */
	protected function checkIntegrity() {
		parent::checkIntegrity();

		if (empty($this->fieldName)) {
			throw new Exception\Domain('Missing config: field name.');
		}
	}

	/**
	 * Configure the tracked field name.
	 *
	 * @param string $name
	 * @return SingleFieldParser $this
	 */
	protected function setName($name) {
		return $this->setFieldName($name);
	}

	/**
	 * @param string $fieldName
	 * @return SingleFieldParser $this
	 */
	public function setFieldName($fieldName) {
		$this->fieldName = $fieldName;
		return $this;
	}

	/**
	 * @return string
	 */
	protected function getFieldName() {
		return $this->fieldName;
	}

	/**
	 * @inheritdoc
	 */
	public function readValues(Model $model, array $fields = null) {
		$fieldName = $this->getFieldName();
		if ($fields === null || in_array($fieldName, $fields)) {
			return array(
				$fieldName => $model->getField($fieldName),
			);
		} else {
			return array();
		}
	}

	/**
	 * @inheritdoc
	 *
	 * Proxies the call to {@link getDeltaRecord()} method, and ensures the returned result is an array.
	 */
	protected function doGetDeltaRecords(array $originalValues, Model $modifiedModel, array $fields) {
		if ($fields) {
			$record = $this->getDeltaRecord($originalValues, $modifiedModel);
			if ($record) {
				return is_array($record)
					? $record
					: array($record);
			}
		}
	}

	/**
	 * Get the delta record for the tracked field, if it has been modified.
	 *
	 * This method should be used/overridden preferably to {@link doGetDeltaRecords()}, in order to
	 * benefit from boilerplate code in there.
	 *
	 * @param array $originalValues
	 * @param Model $modifiedModel
	 * @return DeltaRecordInterface
	 */
	protected function getDeltaRecord(array $originalValues, Model $modifiedModel) {

		$fieldName = $this->getFieldName();
		$newValues = $this->readValues($modifiedModel);

		$originalValue = $originalValues[$fieldName];
		$modifiedValue = $newValues[$fieldName];

		if ($originalValue !== $modifiedValue) {

			$table = $this->getTable();
			$field = $table->getField($fieldName);
			$actualField = $field->getActualField();

			$sqlField = $actualField instanceof ModelColumn
				? $actualField->getName()
				: null;

			return $this->createDeltaRecord(array(
				'XField' => array(
					'sql_table' => $table->getDbTableName(),
					'sql_field' => $sqlField,
					'previous_value' => $originalValue,
					'new_value' => $modifiedValue,
				),
			));
		}
	}

	/**
	 * @inheritdoc
	 */
	protected function createDeltaRecord(array $values = array()) {

		$fieldName = $this->getFieldName();
		$field = $this->getTable()->getField($fieldName);

		$defaults = array(
			'model_field' => $fieldName,
			'field_label' => $this->getFieldLabel($field),
		);

		return parent::createDeltaRecord(array_merge($defaults, $values));
	}

	/**
	 * Get the label of the tracked field.
	 *
	 * History tracking of a field requires that this field's label meta be set (or a DomainException will be
	 * raised).
	 *
	 * @param ModelField $field The tracked field. Can be provided if it is available in the calling context,
	 * to avoid retrieving it twice.
	 * @return string
	 * @throws Exception\Domain
	 */
	protected function getFieldLabel(ModelField $field = null) {
		if ($field === null) {
			$fieldName = $this->getFieldName();
			$field = $this->getTable()->getField($fieldName);
		}

		/** @noinspection PhpUndefinedFieldInspection */
		$label = $field->getMeta()->label;

		if (!isset($label)) {
			throw new Exception\Domain('The label meta property of the field ' . $field->getName() . ' must be set.');
		}

		return $label;
	}
}
