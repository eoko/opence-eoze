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

/**
 * Delta parser for has many relations.
 *
 * @category Eoze
 * @package CqlixHistory
 * @subpackage Delta
 * @since 2013-04-03 10:51
 */
class HasMany extends AbstractRelation {

	/**
	 * @inheritdoc
	 */
	public function readValues(Model $model, array $fields = null) {
		if ($fields === null) {
			$fields = $this->getTrackedFieldNames();
		}

		$fieldName = $this->getFieldName();
		$values = array();

		if (in_array($fieldName, $fields)) {
			$relationName = $this->getRelationName();
			/** @var \ModelSet $set */
			$set = $model->getRelation($relationName)->get();
			$values[$fieldName] = $set->toArray();
		}

		return $values;
	}

	/**
	 * @inheritdoc
	 */
	protected function doGetDeltaRecords(array $originalValues, Model $modifiedModel, array $fields) {

		if (!$fields) {
			return null;
		}

		$fieldName = $this->getFieldName();
		$relationName = $this->getRelationName();

		/** @var $originalModels Model[] */
		$originalModels = $originalValues[$fieldName];
		/** @var $newModels Model[] */
		$newModels = $modifiedModel->getRelation($relationName)->get();

		$newIds = array();
		$oldIds = array();
		$deltaRecords = array();

		foreach ($newModels as $relatedModel) {
			$newIds[] = $relatedModel->getPrimaryKeyValue();
		}
		foreach ($originalModels as $relatedModel) {
			$oldIds[] = $relatedModel->getPrimaryKeyValue();
		}

		foreach ($newModels as $relatedModel) {
			$id = $relatedModel->getPrimaryKeyValue();
			if (!in_array($id, $oldIds, true)) {
				$deltaRecords[] = $this->createDeltaRecordForModel($relatedModel, self::MODIFICATION_ADDED);
			}
		}
		foreach ($originalModels as $relatedModel) {
			$id = $relatedModel->getPrimaryKeyValue();
			if (!in_array($id, $newIds, true)) {
				$deltaRecords[] = $this->createDeltaRecordForModel($relatedModel, self::MODIFICATION_REMOVED);
			}
		}

		return $deltaRecords;
	}
}
