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

namespace eoko\cqlix\Model\Relation;

use Model;
use ModelRelation;
use ModelRelationIndirectHasMany;
use ModelSet;

/**
 *
 * @category Opence
 * @package
 * @subpackage
 * @since 2013-04-16 12:06
 */
class BridgeManyToOne extends ModelRelationIndirectHasMany {

	protected function doSet($values, $forceAcceptNull = false) {

		$ownerRecord = $this->parentModel;
		$ownerRecordId = $ownerRecord->getPrimaryKeyValue();

		$this->assocModels = array();

		if ($values) {

			/** @var $info Info\BridgeManyToOne */
			$info = $this->info;
			$assocTargetRelationName = $info->getAssocTableTargetRelationInfo(true)->getName();

			$leftForeignKey = $this->localForeignKey;
			$rightForeignKey = $this->otherForeignKey;

			foreach ($values as $value) {
				$assocData = array();

				if (false === ($value instanceof Model)) {
					// data
					if (is_array($value)) {
						$assocData = array(
							$leftForeignKey => $ownerRecordId,
							$assocTargetRelationName => $value,
						);
					}
					// id
					else {
						$assocData = array(
							$leftForeignKey => $ownerRecordId,
							$rightForeignKey => $value,
						);
					}
				}

				$assocRecord = $this->assocTable->createModel(
					$assocData, false,
					$this->parentModel->context
				);

				if ($this->parentModel->isNew()) {
					$assocRecord->setFieldFromModelPk($this->localForeignKey, $this->parentModel);
				}

				$this->assocModels[] = $assocRecord;
			}
		}
	}

	public function save() {
		if ($this->isModified()) {

			$assocTable = $this->getAssocTable();
			$leftKey = $this->localForeignKey;
			$rightKey = $this->otherForeignKey;

			/** @var $existingAssocModels Model[] */
			$existingAssocModels = $this->getExistingAssocModels(ModelSet::RANDOM_ACCESS);

			$isUnique = $assocTable->isUniqueBy($leftKey, $rightKey);

			foreach ($this->assocModels as $assocRecord) {

				// Try to match records in the database with generated assoc records
				if ($isUnique) {
					foreach ($existingAssocModels as $i => $existingAssocRecord) {
						if (
							// The target foreign key can be undetermined, if the target is new
							!$assocRecord->isUndetermined($rightKey)
							// Compare left & right foreign keys, guaranteed to be enough by isUniqueBy
							&& $existingAssocRecord->getField($leftKey) === $assocRecord->getField($leftKey)
							&& $existingAssocRecord->getField($rightKey) === $assocRecord->getField($rightKey)
						) {
							// modify the existing record
							$pk = $existingAssocRecord->getPrimaryKeyValue();
							$assocRecord->setPrimaryKeyValue($pk);
							// do not delete this one
							unset($existingAssocModels[$i]);
							// next!
							break;
						}
					}
				}

				$assocRecord->save();
			}

			// If there remains some old assoc records
			if ($existingAssocModels) {
				// Delete remaining unused assoc records
				foreach ($existingAssocModels as $assocRecord) {
					$assocRecord->delete();
				}
			}

			return true;
		}

		return true;
	}
}
