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

namespace eoko\cqlix\Query;
use eoko\cqlix\Aliaser;

/**
 * Interface for Query clause classes.
 *
 * @since 2013-05-16 12:36
 */
interface Clause {

	/**
	 * Builds the SQL string for this clause, populating the provided bindings array as needed.
	 *
	 * @param Aliaser $aliaser
	 * @param array $bindings
	 * @return string
	 */
	public function buildSql(Aliaser $aliaser, array &$bindings);

	/**
	 * Returns `true` if this clause will not have any effect on the query (and so, will not
	 * be built). When this method returns `true`, it is acceptable for the clause's
	 * {@link Clause::buildSql()} method to crash or return invalid SQL, so it is the
	 * responsibility of the using code to handle this situation.
	 *
	 * @return bool
	 */
	public function isEmpty();
}
