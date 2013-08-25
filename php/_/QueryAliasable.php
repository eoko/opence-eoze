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

use eoko\cqlix\Aliaser;

/**
 *
 * @since 2013-05-16 16:21 (Extracted from Query.php)
 */
interface QueryAliasable extends Aliaser {

	function getQualifiedName($fieldName, $table = QueryJoin::TABLE_RIGHT);

	function convertQualifiedNames($preSql, &$bindings);

	/**
	 * Make the given relation name relative to the aliasable. The validity or
	 * existence of the relation itself will not be checked; all that method
	 * does is to prepend the correct prefix to make the given name relative
	 * to itself.
	 *
	 * This method will only work with relation names, and produce chained
	 * relation aliases. Use {@link QueryAliasable::getQualifiedName()} in
	 * order to get name of fields in SQL format.
	 *
	 * @see QueryAliasable::getRelationInfo() to directly access a relation's
	 * info object (thus, ensuring the relation actually exists).
	 */
	function makeRelationName($targetRelationName);

	/**
	 * @param string $targetRelationName
	 * @param bool $requireType
	 * @return ModelRelationInfo
	 */
	function getRelationInfo($targetRelationName, $requireType = false);

	/**
	 * @param string|mixed $conditions
	 * @param mixed|mixed[]|null $inputs
	 * @return QueryWhere
	 */
	function createWhere($conditions = null, $inputs = null);

	/**
	 * @return ModelTableQuery
	 */
	function getQuery();

	/**
	 * @return array
	 */
	function &getContext();
}
