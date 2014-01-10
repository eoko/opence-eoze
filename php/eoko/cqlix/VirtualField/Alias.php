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
 * Virtual field that aliases another field.
 *
 * @internal Field type cannot be retrieved in the constructor, at the risk of going into
 * tables required each other in an infinite loop.
 *
 * @since 2013-10-02 14:48
 */
class Alias extends AbstractVirtualField {

	/**
	 * @var string
	 */
	private $fieldName;

	/**
	 * @var \ModelTable
	 */
	private $table;

	/**
	 * @param \ModelTable $table Model table. Used for lazy retrieval of the field type.
	 * @param string $fieldName Name of the aliased field. Can be relative.
	 * @param string $alias
	 * @internal param string $type Field type. Use constants from {@link ModelField}
	 */
	public function __construct(ModelTable $table, $fieldName, $alias = null) {
		$this->fieldName = $fieldName;
		$this->table = $table;
		parent::__construct($alias);
	}

	/**
	 * Creates an Alias virtual field if the spec if of the form:
	 *
	 *     ALIAS($fieldName)
	 *     alias($fieldName)
	 *
	 * @param ModelTable $table
	 * @param string $spec
	 * @param string|null $name
	 * @return RelationCount
	 */
	public static function fromString(ModelTable $table, $spec, $name = null) {
		if (preg_match('/^ALIAS\((?<fieldName>[^)]+)\)$/i', $spec, $matches)) {
			$fieldName = $matches['fieldName'];
			return new self($table, $fieldName, $name);
		}
	}

	public function getType() {
		if ($this->type === null) {
			$this->type = $this->table->getField($this->fieldName)->getType();
		}
		return $this->type;
	}

	protected function getSQl() {
		return "`$this->fieldName`";
	}
}
