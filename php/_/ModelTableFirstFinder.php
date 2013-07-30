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
 * @method ModelTableFirstFinder where($condition, $inputs)
 * @method ModelTableFirstFinder whereIn($field, $values)
 * @method ModelTableFirstFinder whereNotIn($field, $values)
 * @method ModelTableFirstFinder andWhere($condition, $inputs)
 * @method ModelTableFirstFinder andWhereIn($field, $values)
 * @method ModelTableFirstFinder andWhereNotIn($field, $values)
 * @method ModelTableFirstFinder orWhere($condition, $inputs)
 * @method ModelTableFirstFinder orWhereIn($field, $values)
 * @method ModelTableFirstFinder orWhereNotIn($field, $values)
 *
 * @since 2013-05-16 14:29 (Extracted from ModelTable.php)
 */
class ModelTableFirstFinder extends QueryWhere {

	/** @var ModelTable */
	private $table;

	public function __construct(ModelTable $table, $condition = null, $inputs = null) {
		$this->table = $table;
		if ($condition !== null) $this->where($condition, $inputs);
	}

	/**
	 *
	 * @return Query
	 */
	public function getQuery() {
		return $this->table->createQuery()->selectFirst()->where($this);
	}

	/**
	 * Execute the search query and returns the result as a {@link ModelSet}
	 * @return Model
	 */
	public function execute() {
		$result = $this->table->createQuery()->where($this)->executeSelectFirst();
		if ($result === null) return null;
		else return $this->table->createModel($result, true);
	}

	public function __toString() {
		return $this->table->createQuery()->selectFirst()->where($this)->__toString();
	}

}
