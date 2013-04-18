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

namespace eoko\modules\EozeExt4\controller\Rest\Cqlix;

use Model as Record;

/**
 * Proxy used by this module's controllers to read and write to Cqlix records.
 *
 * @since 2013-04-18 10:34
 */
interface DataProxy {

	/**
	 * Creates a record with the specified data.
	 *
	 * @param array $data
	 * @return Record
	 */
	public function createRecord(array $data = null);

	/**
	 * Loads the specified record. If the record does not exist, this method will return
	 * null.
	 *
	 * @param mixed $id
	 * @return Record|null
	 */
	public function loadRecord($id);

	/**
	 * Gets the formatted data for the given record.
	 *
	 * @param Record $record
	 * @return array
	 */
	public function getRecordData(Record $record);

	/**
	 * Update the passed record with the given data.
	 *
	 * @param Record $record
	 * @param array $data
	 */
	public function setRecordData(Record $record, array $data);
}
