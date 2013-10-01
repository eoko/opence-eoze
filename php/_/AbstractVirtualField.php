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
 * @todo doc
 *
 * @since 2013-10-02 12:59
 */
class AbstractVirtualField extends VirtualFieldBase {

	protected $alias = true;

	protected function doGetClause(Aliaser $aliaser) {
		if (null !== $sql = $this->getSql($aliaser)) {
			return $aliaser->aliases($sql);
		} else {
			return parent::doGetClause($aliaser);
		}
	}

	/**
	 * This method can be implemented instead of {@link doGetClause()}, and offers
	 * a more concise syntax for virtual field that just returns some SQL statement.
	 *
	 * The statement can contains Cqlix field names relative to the model owning the
	 * virtual field. These names will automatically be converted to fully qualified
	 * names; the `Aliaser` is provided only for more complex operations.
	 *
	 * E.g.
	 *
	 *     protected function getSql() {
	 *         return 'CONT(`ChildModel->id`);
	 *     }
	 *
	 * @param eoko\cqlix\Aliaser $aliaser
	 * @return string
	 */
	protected function getSql(Aliaser $aliaser) {
		return null;
	}
}
