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

namespace eoko\modules\CqlixHistory\Entry;

use HistoryEntry;

/**
 * Deletion entry.
 *
 * @category Eoze
 * @package CqlixHistory
 * @subpackage Entry
 * @since 2013-04-02 14:36
 */
class Deletion extends AbstractEntry {

	/**
	 * @inheritdoc
	 */
	protected $operation = self::OPERATION_DELETE;

	/**
	 * @inheritdoc
	 *
	 * This method doesn't produce any delta, it just returns true so that the entry record will
	 * be saved.
	 */
	protected function populateEntry(HistoryEntry $entry) {
		return true;
	}
}
