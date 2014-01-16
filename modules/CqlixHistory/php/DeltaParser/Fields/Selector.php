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

namespace eoko\modules\CqlixHistory\DeltaParser\Fields;

use ModelTable;

/**
 * Interface for classes that select the fields to be processed by a {@link eoko\modules\CqlixHistory\DeltaParser\Fields
 * multiple fields delta parser}.
 *
 * @category Eoze
 * @package CqlixHistory
 * @subpackage DeltaParser
 *
 * @since 2013-04-03 11:44
 */
interface Selector {

	/**
	 * Get the name of the fields that are tracked by the associated multiple fields delta parser.
	 *
	 * @param ModelTable $table
	 * @return string[]
	 */
	public function getTrackedFieldNames(ModelTable $table);
}
