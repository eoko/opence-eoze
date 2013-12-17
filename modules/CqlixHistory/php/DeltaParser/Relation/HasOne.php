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

namespace eoko\modules\CqlixHistory\DeltaParser\Relation;

use Model;
use eoko\modules\CqlixHistory\DeltaParser\AbstractRelation;
use eoko\modules\CqlixHistory\Exception;

/**
 * Delta parser for has one relations.
 *
 * @category Eoze
 * @package CqlixHistory
 * @subpackage DeltaParser
 * @since 2013-04-04 10:22
 */
class HasOne extends AbstractRelation {

	/**
	 * @inheritdoc
	 */
	public function readValues(Model $model, array $fields = null) {
		$fieldName = $this->getFieldName();
		if ($fields === null || in_array($fieldName, $fields)) {
			return array(
				$fieldName => $model->{$this->getRelationName()},
			);
		} else {
			return array();
		}
	}

	/**
	 * @inheritdoc
	 */
	protected function getDeltaRecord(array $originalValues, Model $modifiedModel) {

		$relationName = $this->getRelationName();
		$fieldName = $this->getFieldName();

		$newValues = $this->readValues($modifiedModel);

		/** @var $originalTarget Model */
		$originalTarget = $originalValues[$fieldName];
		/** @var $modifiedTarget Model */
		$modifiedTarget = $newValues[$fieldName];

		$previousValue = $originalTarget
			? $originalTarget->getPrimaryKeyValue()
			: null;
		$newValue = $modifiedTarget ?
			$modifiedTarget->getPrimaryKeyValue()
			: null;

		if ($previousValue !== $newValue) {
			$table = $this->getTable();

			$relationInfo = $table->getRelationInfo($relationName);

			// Label
			$fieldLabel = $this->getFieldLabel($relationInfo);

			// SQL Table
			if ($relationInfo instanceof \ModelRelationInfoHasReference) {
				$sqlTable = $table->getDbTable();
			} else if ($relationInfo instanceof \ModelRelationInfoIsRefered) {
				$sqlTable = $relationInfo->getTargetTable();
			} else {
				throw new Exception\IllegalState();
			}

			$deltaRecord = $this->createDeltaRecord(array(
				'model_field' => $relationName,
				'field_label' => $fieldLabel,
				'XField' => array(
					'sql_table' => $sqlTable,
					'sql_field' => $relationInfo->getReferenceField()->getName(),
					'previous_value' => $previousValue,
					'new_value' => $newValue,
					'previous_value_display' => $this->extractModelFriendlyName($originalTarget),
					'new_value_display' => $this->extractModelFriendlyName($modifiedTarget),
				),
			));
			return $deltaRecord;
		}
	}

}
