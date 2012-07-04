<?php

namespace eoze\util\DataStore\Query;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 2 nov. 2011
 */
class CompositeFilter extends Filter {
	
	private $filters;
	
	public function __construct() {
		foreach (func_get_args() as $filter) {
			$this->add($filter);
		}
	}
	
	public function add(Filter $filter) {
		$this->filters[] = $filter;
	}
	
	public function test($value) {
		foreach ($this->filters as $filter) {
			if (!$filter->test($value)) {
				return false;
			}
		}
		return true;
	}
}
