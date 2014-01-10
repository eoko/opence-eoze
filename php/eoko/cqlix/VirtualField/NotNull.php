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

namespace eoko\cqlix\VirtualField;
use ModelTable;

/**
 * Boolean virtual field that is true when the specified field is not null.
 *
 * @since 2013-10-02 14:36
 */
class NotNull extends AbstractVirtualField {

	protected $type = self::T_BOOL;

	/**
	 * @var null
	 */
	private $field;

	/**
	 * Creates a NotNull virtual field if the spec if of the form:
	 *
	 *     $fieldName IS NOT NULL
	 *
	 *     `$fieldName` IS NOT NULL
	 *
	 *     $fieldName is not null
	 *
	 * @param ModelTable $table
	 * @param string $spec
	 * @param string|null $name
	 * @return RelationCount
	 */
	public static function fromString(ModelTable $table, $spec, $name = null) {
		if (preg_match('/^`?(?<fieldName>[^)`]+)`? IS NOT NULL$/i', $spec, $matches)) {
			return new self($matches['fieldName'], $name);
		}
	}

	public function __construct($field, $alias = null) {
		$this->field = $field;
		parent::__construct($alias);
	}

	protected function getSql() {
		return "`$this->field` IS NOT NULL";
	}
}
