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

namespace eoko\modules\EozeExt4\controller\Rest\Cqlix\DataProxy\TableProxy;

/**
 * Utility class that caches the results of a single column select query, based on
 * another provided query.
 *
 * @since 2013-05-01 12:36
 */
class IdQueryCache {

	/**
	 * @var \Query
	 */
	private $query;

	/**
	 * @var array[]
	 */
	private $cacheByField;

	/**
	 * Creates a new id cache object.
	 *
	 * @param \Query $query
	 */
	public function __construct(\Query $query) {
		$this->query = $query;
	}

	/**
	 * Gets the values of the specified field in the underlying query. The result will be
	 * cached, so multiple calls of this method for the same field name won't induce extra
	 * query to the database.
	 *
	 * @param string $fieldName
	 * @return int[]
	 */
	public function getIds($fieldName) {
		if (!isset($this->cacheByField[$fieldName])) {
			// Parent ids query
			$idQuery = clone $this->query;
			// The query will be limited either (1) by the initial limit if the working
			// query is the root query, or (2) the WHERE IN clause if the working query
			// is already a child of the root query.
			$idQuery
				->resetSelect()
				->select($fieldName);

			$this->cacheByField[$fieldName] = $idQuery->executeSelectColumn();
		}
		return $this->cacheByField[$fieldName];
	}
}
