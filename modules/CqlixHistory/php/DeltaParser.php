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

namespace eoko\modules\CqlixHistory;

use Model;

/**
 * Interface for parsers of delta records at the field level.
 *
 * @category Eoze
 * @package CqlixHistory
 * @subpackage DeltaParser
 * @since 2013-04-03 11:24
 */
interface DeltaParser extends Enum {

	/**
	 * Computes the difference between the two models.
	 *
	 * Excluded fields
	 * ---------------
	 *
	 * This method accepts an optional black list of fields to process. If such a list is provided,
	 * then the parser **MUST NOT** create records for any field that is in the list (even if the
	 * field has been modified and otherwise meet all the conditions to have a delta record
	 * generated).
	 *
	 * The black list is used to prevent multiple parsers from generating a delta record for the
	 * same field.
	 *
	 * The black list must be passed as an associative array in which the excluded field names are
	 * used as the keys, and the associated value is always `true`.
	 *
	 * @param array $originalValues
	 * @param Model $modifiedModel
	 * @param bool[] $excludedFieldsMap
	 * @throws Exception\Domain If the parser configuration is incomplete.
	 * @return DeltaRecordInterface[]
	 */
	public function getDeltaRecords(array $originalValues, Model $modifiedModel, array $excludedFieldsMap = null);

	/**
	 * @todo doc
	 *
	 * @param Model $model
	 * @param array $fields
	 * @return array
	 */
	public function readValues(Model $model, array $fields = null);

	/**
	 * Get the name of the fields that are tracked by this parser. This is needed in order to
	 * be able to avoid parsing twice the same field.
	 *
	 * The returned array must contains all the fields for which this parser may generate a
	 * delta record (that is, even if no record will actually be created because the tracked
	 * field was not modified).
	 *
	 * @return string[]
	 */
	public function getTrackedFieldNames();
}
