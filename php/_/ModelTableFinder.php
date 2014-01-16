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

/**
 * @method ModelTableFinder where($condition, $inputs)
 * @method ModelTableFinder whereIn($field, $values)
 * @method ModelTableFinder whereNotIn($field, $values)
 * @method ModelTableFinder andWhere($condition, $inputs)
 * @method ModelTableFinder andWhereIn($field, $values)
 * @method ModelTableFinder andWhereNotIn($field, $values)
 * @method ModelTableFinder orWhere($condition, $inputs)
 * @method ModelTableFinder orWhereIn($field, $values)
 * @method ModelTableFinder orWhereNotIn($field, $values)
 *
 * @since 2013-05-16 14:28 (Extracted from ModelTable.php)
 */
class ModelTableFinder extends QueryWhere {

	/** @var ModelTable */
	private $table;
	/** @var ModelTableQuery */
	public $query;

	public function __construct(ModelTable $table, $condition = null, $inputs = null, $context = null) {
		$this->table = $table;
		$this->query = $this->table->createQuery($context);
		parent::__construct($this->query, $condition, $inputs);
	}

	/**
	 * Accesses the query used internally by the Finder.
	 * @return \ModelTableQuery
	 */
	public function getQuery() {
		return $this->query;
	}

	/**
	 * Gets a clone of the finder's query.
	 * @return \ModelTableQuery
	 */
	public function cloneQuery() {
		$q = clone $this->query;
		$q->where($this)->select();
		return $q;
	}

	/**
	 * @param int $mode one of the {@link ModelSet}
	format constants
	 * @return ModelSet
	 */
	public function execute($mode = ModelSet::ONE_PASS) {
		$this->query->andWhere($this);
		return ModelSet::create($this->table, $this->query, $mode);
	}

}
