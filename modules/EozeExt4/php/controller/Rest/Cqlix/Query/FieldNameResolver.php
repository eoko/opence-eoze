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

namespace eoko\modules\EozeExt4\controller\Rest\Cqlix\Query;

use eoko\modules\EozeExt4\controller\Rest\Cqlix\Exception\UnknownField as UnknownFieldException;

/**
 * Interface for resolver of client field names to server field names used by {@link Processor query processors}.
 *
 * @since 2013-05-17 15:13
 */
interface FieldNameResolver {

	/**
	 * Resolves the server field name for the specified client field.
	 *
	 * Nested client fields can be accessed:
	 *
	 * - either with dot notation (e.g. `myRelationField.targetField`),
	 * - or the successive field names can be passed as an array (e.g. `array('myRelationField', 'targetField')`)
	 *
	 * @param string|string[] $clientFieldName
	 * @param bool $require
	 * @throws UnknownFieldException
	 * @return string|null
	 */
	public function getServerFieldName($clientFieldName, $require = false);
}
