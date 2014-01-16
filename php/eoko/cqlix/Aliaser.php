<?php
/**
 * Copyright (C) 2012 Eoko
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
 * @copyright Copyright (C) 2012 Eoko
 * @licence http://www.gnu.org/licenses/gpl.txt GPLv3
 * @author Éric Ortega <eric@eoko.fr>
 */

namespace eoko\cqlix;

use ModelTableQuery;
use eoko\cqlix\Exception\RequireBindingsException;

/**
 *
 * @category Opence
 * @package
 * @subpackage
 * @since 2012-12-11 12:14
 */
interface Aliaser {

	/**
	 * Gets the `Query` to which this `Aliaser` is attached.
	 *
	 * @return ModelTableQuery
	 */
	function getQuery();

	/**
	 * Converts the given name to its fully qualified name or clause, relative to this aliaser.
	 *
	 * @param string $name
	 * @return string
	 */
	function alias($name);

	/**
	 * Converts all identifiers in the given clause to their fully qualified name or clause,
	 * relative to this aliaser.
	 *
	 * This method will throw a `RequireBindingsException` if no bindings variable is provided,
	 * but the conversion requires it.
	 *
	 * @param string $clause
	 * @param array &$bindings
	 * @throws RequireBindingsException
	 * @return string
	 */
	function aliases($clause, array &$bindings = null);
}
