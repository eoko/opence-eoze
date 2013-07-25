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
use ModelTable;
use ModelColumn;
use eoko\modules\CqlixHistory\DeltaParser\Fields\Selector;
use eoko\modules\CqlixHistory\DeltaParser\Fields\SelectorFactory;

/**
 * Parser for multiple model fields.
 *
 * @category Eoze
 * @package CqlixHistory
 * @subpackage DeltaParser
 * @since 2013-04-03 11:20
 */
class Fields extends AbstractParser {

	/**
	 * @var Selector
	 */
	private $fieldSelector;

	/**
	 * Configure the field selector.
	 *
	 * @param Selector|array $selector
	 */
	public function setFieldSelector($selector) {
		$this->fieldSelector = SelectorFactory::create($selector);
	}

	/**
	 * @return Selector
	 */
	protected function getFieldSelector() {
		if (!$this->fieldSelector) {
			$this->fieldSelector = SelectorFactory::createDefault();
		}
		return $this->fieldSelector;
	}

	/**
	 * @inheritdoc
	 */
	public function readValues(Model $model, array $fields = null) {
		if ($fields === null) {
			$fields = $this->getTrackedFieldNames();
		}

		$values = array();
		foreach ($fields as $fieldName) {
			$values[$fieldName] = $model->$fieldName;
		}
		return $values;
	}

	/**
	 * @inheritdoc
	 */
	protected function doGetDeltaRecords(array $originalValues, Model $modifiedModel, array $fields) {

		$table = $this->getTable();

		$deltaRecords = array();

		$newValues = $this->readValues($modifiedModel, $fields);

		foreach ($fields as $fieldName) {

			$newValue = $newValues[$fieldName];
			$previousValue = $originalValues[$fieldName];

			if ($previousValue !== $newValue) {
				$field = $table->getField($fieldName);
				$fieldLabel = $field->getMeta()->get('label', $fieldName);

				$deltaRecords[] = $this->createDeltaRecord(array(
					'model_field' => $fieldName,
					'field_label' => $fieldLabel,
					'XField' => array(
						'sql_table' => $table->getDbTable(),
						'sql_field' => $fieldName,
						'previous_value' => $previousValue,
						'new_value' => $newValue,
					),
				));
			}
		}

		return $deltaRecords;
	}

	/**
	 * @inheritdoc
	 */
	public function getTrackedFieldNames() {
		$table = $this->getTable();
		$selector = $this->getFieldSelector();
		return $selector->getTrackedFieldNames($table);
	}
}
