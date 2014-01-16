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
 * A set containing {@lin Model models}.
 *
 * @since 2013-05-16 13:41 (Extracted from file ModelTable.php)
 */
abstract class ModelSet implements Iterator {

	const RAW = -1;
	const ONE_PASS = 0;
	const RANDOM_ACCESS = 1;

	/**
	 * Get the number of records in this set.
	 *
	 * @return int
	 */
	abstract public function count();

	/**
	 * @return Model[]
	 */
	abstract public function toArray();

	/**
	 * Creates
	 *
	 * @param \ModelTableProxy $table
	 * @param Query $query
	 * @param int|string $mode
	 * @param \ModelRelationReciproqueFactory $reciproqueFactory
	 * @throws \IllegalArgumentException
	 * @return ModelSet
	 */
	public static function create(
		ModelTableProxy $table,
		Query $query,
		$mode = self::ONE_PASS,
		ModelRelationReciproqueFactory $reciproqueFactory = null
	) {

		switch ($mode) {
			case self::ONE_PASS:
				return new OnePassModelSet($table, $query, $reciproqueFactory);
				break;
			case self::RANDOM_ACCESS:
				return new RandomAccessModelSet($table, $query, $reciproqueFactory);
				break;
			case self::RAW:
				return $query->executeSelect();
			default:
				throw new IllegalArgumentException('Unknown mode: ' . $mode);
		}
	}

	public static function createEmpty(ModelTableProxy $table, $mode = ModelSet::RANDOM_ACCESS) {
		switch ($mode) {
			case self::ONE_PASS:
				throw new IllegalArgumentException('There is absolutly no point in creating'
				. 'an empty one-pass model set...');
				break;
			case self::RANDOM_ACCESS:
				return new RandomAccessModelSet($table, null);
				break;
			case self::RAW:
				return array();
			default:
				throw new IllegalArgumentException('Unknown mode: ' . $mode);
		}
	}

	public function __toString() {
		$r = get_class($this) . '[';
		$empty = true;
		foreach ($this as $k => $v) {
			$empty = false;
			$r .= "\n\t$k => " . $v;
		}
		return $r . ($empty ? '' : "\n") . ']';
	}
}
