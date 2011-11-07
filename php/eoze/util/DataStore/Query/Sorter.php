<?php

namespace eoze\util\DataStore\Query;

use eoze\util\DataStore;

use IllegalArgumentException;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 2 nov. 2011
 */
class Sorter extends QueryElementOnField {
	
	protected $type = self::SORTER;
	
	const ASC  =  1;
	const DESC = -1;

	protected $direction = 1;
	
	public function __construct($field, $direction = null) {
		parent::__construct($field);
		if ($direction !== null) {
			if (is_string($direction)) {
				$direction = strtolower($direction);
				if ($direction === 'asc') {
					$direction = self::ASC;
				} else if ($direction === 'desc') {
					$direction = self::DESC;
				} else if (is_numeric($direction)) {
					$this->direction = (int) $direction;
					if ($this->direction === 0) {
						throw new IllegalArgumentException();
					}
				} else {
					throw new IllegalArgumentException();
				}
			}
			$this->direction = $direction;
		}
	}

	public function getSortInfo() {
		return array($this->field => $this->direction);
	}
	
	protected function doCompare($leftValue, $rightValue) {
		if (is_numeric($leftValue) && is_numeric($rightValue)) {
			return ($leftValue - $rightValue) * $this->direction;
		} else {
			return strnatcmp($leftValue, $rightValue) * $this->direction;
		}
	}
	
	public function compare($leftElement, $rightElement) {
		return $this->doCompare(
				$this->getFieldValue($leftElement), 
				$this->getFieldValue($rightElement));
	}
}
