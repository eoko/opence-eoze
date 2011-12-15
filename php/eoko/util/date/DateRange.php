<?php

namespace eoko\util\date;

use IllegalArgumentException;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 15 déc. 2011
 */
class DateRange {
	
	/**
	 * @var Date
	 */
	private $from;
	/**
	 * @var Date
	 */
	private $to;
	
	public function __construct($from, $to) {
		$this->from = Date::parseDate($from);
		$this->to = Date::parseDate($to);
		
		if ($this->from->after($this->to)) {
			$from = $this->from->format('Y-m-d');
			$to = $this->to->format('Y-m-d');
			throw new IllegalArgumentException("Date from ($from) must be before date to ($to)");
		}
	}

	/**
	 * @param DateRange $other
	 * @return DateRange 
	 */
	public function intersect(DateRange $other) {
		
		if (!$this->to->afterOrEquals($other->from)
				|| !$this->from->beforeOrEquals($other->to)) {
			return null;
		}
		
		return new DateRange(
			$this->from->afterOrEquals($other->from) ? $this->from : $other->from,
			$this->to->beforeOrEquals($other->to) ? $this->to : $other->to
		);
	}
	
	public function toStringArray($format = 'Y-m-d') {
		return array($this->from->format($format), $this->to->format('Y-m-d'));
	}
}
