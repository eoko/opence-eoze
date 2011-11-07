<?php

namespace eoze\util\DataStore\Query;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 2 nov. 2011
 */
class CompositeSorter extends Sorter {
	
	private $sorters;

	public function __construct() {
		foreach (func_get_args() as $sorter) {
			$this->add($sorter);
		}
	}
	
	public function add(Sorter $sorter) {
		$this->sorters[] = $sorter;
	}
	
	public function compare($left, $right) {
		foreach ($this->sorters as $sorter) {
			if (0 !== $diff = $sorter->compare($left, $right)) {
				return $diff;
			}
		}
		return 0;
	}
	
	public function getSortInfo() {
		$r = array();
		foreach ($this->sorters as $s) {
			$r[$s->field] = $r[$s->direction];
		}
		return $r;
	}
}
