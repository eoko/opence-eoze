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

namespace eoko\cqlix\Query\Order;
use eoko\cqlix\Aliaser;
use eoko\cqlix\Query\Clause\AbstractClause;
use eoko\cqlix\Query\Clause;

/**
 * @todo doc
 *
 * @since 2013-07-15 16:02
 */
class FuzzySearchScore implements Clause {

	/**
	 * @var Clause[]
	 */
	private $clauses = array();

	public function __construct($fields, $queryParts, $dir = 'DESC') {
		if (!is_array($fields)) {
			$fields = array($fields);
		}

		$fields = array_map(function($field) {
			if (substr($field, 0, 1) === '`') {
				return $field;
			} else {
				return "`$field`";
			}
		}, $fields);

//		$this->_build($fields, $queryParts, $dir);
//	}
//
//	public function _build($fields, $queryParts, $dir) {
		// Order by:
		// - number of full matches q
		// - number of begin matches q%
		// - number of matches %q%
		foreach (array('q', 'q%', '%q%') as $partQueryMask) {
			$sortScoreClause = array();
			$sortStoreBindings = array();

			foreach ($queryParts as $i => $part) {
				$partQuery = str_replace('q', $part, $partQueryMask);

				$fieldClause = array();
				foreach ($fields as $field) {
					$fieldClause[] = "$field LIKE ?";
					$sortStoreBindings[] = $partQuery;
				}
				$fieldClause = implode(' OR ', $fieldClause);

				$sortScoreClause[] = "IF($fieldClause, 1, 0)";
			}

			$sortScoreClause = PHP_EOL . '(' . implode(' + ', $sortScoreClause) . ') ' . $dir . PHP_EOL;

			$this->clauses[] = new Clause\Custom($sortScoreClause, $sortStoreBindings);
//			$sortScore = new CustomOrderClause($sortScoreClause, $sortStoreBindings);
//			$memberQuery->thenOrderBy($sortScore, 'DESC');
		}
	}

	/**
	 * Builds the SQL string for this clause, populating the provided bindings array as needed.
	 *
	 * @param Aliaser $aliaser
	 * @param array $bindings
	 * @return string
	 */
	public function buildSql(Aliaser $aliaser, array &$bindings) {
		$buffer = array();
		foreach ($this->clauses as $clause) {
			if (!$clause->isEmpty()) {
				$buffer[] = $clause->buildSql($aliaser, $bindings);
			}
		}
		return implode(', ', $buffer);
	}

	/**
	 * Returns `true` if this clause will not have any effect on the query (and so, will not
	 * be built). When this method returns `true`, it is acceptable for the clause's
	 * {@link Clause::buildSql()} method to crash or return invalid SQL, so it is the
	 * responsibility of the using code to handle this situation.
	 *
	 * @return bool
	 */
	public function isEmpty() {
		foreach ($this->clauses as $clause) {
			if (!$clause->isEmpty()) {
				return false;
			}
		}
		return true;
	}
}
