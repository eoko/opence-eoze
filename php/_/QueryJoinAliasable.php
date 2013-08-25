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
use eoko\cqlix\legacy\QueryAliasableToAliaser;

/**
 * @since 2013-04-30 13:51 (Extracted from file QueryJoin.php)
 */
class QueryJoinAliasable implements QueryAliasable {

	/**
	 * @var QueryJoin
	 */
	private $join;

	private $side;

	function __construct($join, $side) {
		$this->join = $join;
		$this->side = $side;
	}

	public function __toString() {
		$side = null;
		switch ($this->side) {
			case QueryJoin::TABLE_LEFT:
				$side = 'LEFT';
				break;
			case QueryJoin::TABLE_RIGHT:
				$side = 'RIGHT';
				break;
			case QueryJoin::TABLE_ASSOC:
				$side = 'ASSOC';
				break;
			default:
				$side = '???';
				break;
		}
		return "Aliasable($side) for: $this->join";
	}

	public function convertQualifiedNames($preSql, &$bindings) {
		return $this->join->convertQualifiedNames($preSql, $bindings);
	}

	public function createWhere($conditions = null, $inputs = null) {
		return $this->join->createWhere($conditions, $inputs);
	}

	public function &getContext() {
		return $this->join->query->context;
	}

	public function getQualifiedName($fieldName, $side = null){
		if ($side === null) {
			$side = $this->side;
		}
		return $this->join->getQualifiedName($fieldName, $side);
	}

	public function getQuery() {
		return $this->join->getQuery();
	}

	public function getRelationInfo($targetRelationName, $requireType = false) {
		return $this->join->getRelationInfo($targetRelationName, $requireType);
	}

	public function makeRelationName($targetRelationName) {
		return $this->join->makeRelationName($targetRelationName);
	}

	/**
	 * @inheritdoc
	 */
	public function alias($name) {
		return $this->getQualifiedName($name);
	}

	/**
	 * @inheritdoc
	 */
	public function aliases($clause, array &$bindings = null) {
		return QueryAliasableToAliaser::aliases($this, $clause, $bindings);
	}
}
