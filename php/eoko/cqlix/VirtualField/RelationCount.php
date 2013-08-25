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

use eoko\cqlix\Aliaser;
use ModelTable;

/**
 * @todo doc
 *
 * @since 2013-06-10 10:32
 */
class RelationCount extends AbstractVirtualField {

	protected $type = self::T_INT;

	private $table;
	private $relation;

	/**
	 * Creates a new RelationCount virtual field.
	 *
	 * If the alias is not specified, one will be generated from the singular relation name, in
	 * camelCase.
	 *
	 * @param ModelTable $table
	 * @param string $relation
	 * @param string|null $alias
	 */
	public function __construct(\ModelTable $table, $relation, $alias = null) {

		if ($alias === null) {
			$alias = lcfirst(\Inflector::singularize($relation)) . 'Count';
		}

		$this->table = $table;
		$this->relation = $relation;

		parent::__construct($alias);
	}

	/**
	 * Creates a RelationCount virtual field if the spec if of the form:
	 *
	 *     COUNT($relationName)
	 *
	 * @param ModelTable $table
	 * @param string $spec
	 * @param string|null $name
	 * @return RelationCount
	 */
	public static function fromString(ModelTable $table, $spec, $name = null) {
		if (preg_match('/^COUNT\((?<relation>[^)]+)\)$/', $spec, $matches)) {
			return new self($table, $matches['relation'], $name);
		}
	}

	/**
	 * @inheritdoc
	 */
	protected function doGetClause(Aliaser $aliaser) {
		$table = $this->table;
		$relationInfo = $table->getRelationInfo($this->relation);
		return $relationInfo->getNameClause($aliaser->getQuery());
	}
}
