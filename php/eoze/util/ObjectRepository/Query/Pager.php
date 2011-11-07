<?php

namespace eoze\util\ObjectRepository\Query;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 2 nov. 2011
 */
class Pager extends QueryElement {

	protected $type = self::PAGER;
	
	private $limit = null;
	
	private $start = null;

	function __construct($limit = null, $start = null) {
		$this->limit = $limit;
		$this->start = $start;
	}
	
	protected function getType() {
		return self::PAGER;
	}
	
	public function getLimit() {
		return $this->limit;
	}

	public function getStart() {
		if ($this->start !== null && $this->start >= 0) {
			return $this->start;
		} else {
			return 0;
		}
	}
	
	public function setLimit($limit) {
		$this->limit = $limit;
	}
	
	public function setStart($start) {
		$this->start = $start;
	}
}
