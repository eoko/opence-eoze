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

use ModelTableQuery;
use eoko\cqlix\Aliaser;

/**
 * This class is used to add an 'ORDER BY FIELD' clause to a {@link Query}.
 *
 * @since 2013-04-30 23:19
 */
class OrderByField implements Clause {

	/**
	 * Creates a new OrderByField object.
	 *
	 * @param string $field
	 * @param array $values
	 */
	public function __construct($field, array $values) {
		$this->field = $field;
		$this->values = $values;
	}

	/**
	 * Returns `true` if the values array is empty.
	 *
	 * @return bool
	 */
	public function isEmpty() {
		return empty($this->values);
	}

	/**
	 * Builds the SQL string for this order clause.
	 *
	 * @param Aliaser $aliaser
	 * @param array $bindings
	 * @return string
	 */
	public function buildSql(Aliaser $aliaser, array &$bindings) {
		$field = $aliaser->alias($this->field);

		$fillers = array_fill(0, count($this->values), '?');
		$fillers = implode(',', $fillers);

		$bindings = array_merge($bindings, $this->values);

		return "FIELD($field, $fillers)";
	}
}
