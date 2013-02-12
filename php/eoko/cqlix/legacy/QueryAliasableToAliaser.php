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

namespace eoko\cqlix\legacy;

/**
 *
 * @category Opence
 * @package
 * @subpackage
 * @since 2012-12-11 15:52
 */
final class QueryAliasableToAliaser {

	public static function aliases(\QueryAliasable $aliasable, $clause, array &$bindings = null) {
		if ($bindings !== null) {
			return $aliasable->convertQualifiedNames($clause, $bindings);
		} else {
			$bindings = array();
			$convertedClause = $aliasable->convertQualifiedNames($clause, $bindings);
			if (count($bindings) > 0) {
				throw new \eoko\cqlix\Exception\RequireBindingsException();
			}
			return $convertedClause;
		}
	}
}
