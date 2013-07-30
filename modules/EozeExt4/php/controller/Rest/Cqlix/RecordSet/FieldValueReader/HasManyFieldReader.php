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

namespace eoko\modules\EozeExt4\controller\Rest\Cqlix\RecordSet\FieldValueReader;

use eoko\modules\EozeExt4\controller\Rest\Cqlix\Record;
use eoko\modules\EozeExt4\controller\Rest\Cqlix\RecordSet;

/**
 * FieldValueReader for reading multiple records as value of an has-many relation.
 *
 * @since 2013-04-30 14:54
 */
class HasManyFieldReader implements RecordSet\FieldValueReader {

	/**
	 * @var string
	 */
	protected $parentPrimaryKeyName;

	/**
	 * Record set containing **all** child records data. That is: all the children of all the records
	 * in the parent set.
	 *
	 * @var RecordSet
	 */
	protected $recordSet;

	/**
	 * @var Record
	 */
	private $nextRecord;

	/**
	 * Creates a new {@link HasManyFieldReader} object.
	 *
	 * @param string $parentPkName
	 * @param RecordSet $childRecordSet
	 */
	public function __construct($parentPkName, RecordSet $childRecordSet) {
		$this->parentPrimaryKeyName = $parentPkName;
		$this->recordSet = $childRecordSet;
		$childRecordSet->rewind();
	}

	/**
	 * @inheritdoc
	 */
	public function readFieldValue($input) {

		$set = $this->recordSet;
		$parentRecordId = $input[$this->parentPrimaryKeyName];

		$records = array();

		while ($set->valid()) {
			$record = $set->current();

			/** @var $record Record */
			if ($record) {
				$parentId = $record->getParentId();

				if ($parentRecordId === $parentId) {
					if ($record !== $this->nextRecord && $this->nextRecord !== null) {
						dump_trace(); // DEBUG THIS
						dump(array(
							$record,
							$this->nextRecord,
						));
					} else {
						$this->nextRecord = null;
					}
					$records[] = $record;
					$set->next();
				} else {
					$this->nextRecord = $record;
					break;
				}
			} else {
				$set->next();
			}
		}

		return $records;
	}

}
