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
 * Alternative implementation of {@link HasManyFieldReader} that builds a lookup of child records.
 * This implementation requires much more memory (to the point it could easily overflow) but is
 * more robust that the default one, because it doesn't depends on the order
 *
 * @since 2013-05-23 14:40
 */
class RobustHasManyFieldReader extends HasManyFieldReader {

	/**
	 * @inheritdoc
	 */
	public function __construct($parentPkName, RecordSet $childRecordSet) {
		parent::__construct($parentPkName, $childRecordSet);

		$this->recordsByParent = array();
		foreach ($childRecordSet as $record) {
			/** @var $record Record */
			if ($record) {
				$this->recordsByParent[$record->getParentId()][] = $record;
			}
		}
	}

	/**
	 * @inheritdoc
	 */
	public function parseData($input) {

		$parentRecordId = $input[$this->parentPrimaryKeyName];

		return isset($this->recordsByParent[$parentRecordId])
			? $this->recordsByParent[$parentRecordId]
			: array();
	}
}
