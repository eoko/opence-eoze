<?php

namespace eoko\util\date;

use IllegalArgumentException;

use DateTimeZone;

/**
 * Represents an **immutable** date range, that is a date from and a date to.
 * 
 * The `getFrom()` and `getTo()` methods return clones of internal Date objects,
 * so they are impossible to modify externaly. That helps preventing bugs with
 * PHP 5.3.2 DateTime handling.
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
	
	/**
	 * Constructs a new DateRange object.
	 * 
	 * Accepts either two dates (either string or DateTime) as aguement, or an
	 * array of exactly two dates (here again, strings or DateTimes).
	 * 
	 * @param string|DateTime|array $from
	 * @param string|DateTime|null $to 
	 */
	public function __construct($from, $to = null) {
		
		if ($to === null) {
			if (is_array($from)) {

				if (count($from) != 2) {
					throw new IllegalArgumentException('$from array must have a length of exactly 2');
				}

				$to = $from[1];
				$from = $from[0];
			} else {
				throw new IllegalArgumentException('First argument must be an array, when called '
						. 'with only one argument.');
			}
		}
		
		$this->from = Date::parseDate($from);
		$this->to = Date::parseDate($to);
		
		if ($this->getFrom()->after($this->getTo())) {
			$from = $this->getFrom()->format('Y-m-d e');
			$to = $this->getTo()->format('Y-m-d e');
			throw new IllegalArgumentException("Date from ($from) must be before date to ($to)");
		}
	}
	
	/**
	 * Returns a **clone** of the from Date.
	 * @return Date
	 */
	public function getFrom() {
		return clone $this->from;
	}
	
	/**
	 * Returns a **clone** of the to Date.
	 * @return Date
	 */
	public function getTo() {
		return clone $this->to;
	}

	/**
	 * Gets the intersection of the current DateRange with the given one. All comparisons
	 * are inclusive.
	 * 
	 * @param DateRange $other
	 * @return DateRange 
	 */
	public function intersect(DateRange $other) {
		
		// If ranges don't intersect at all
		if (!$this->getTo()->afterOrEquals($other->getFrom())
				|| !$this->getFrom()->beforeOrEquals($other->getTo())) {
			return null;
		}
		
		return new DateRange(
			$this->getFrom()->afterOrEquals($other->getFrom()) ? $this->getFrom() : $other->getFrom(),
			$this->getTo()->beforeOrEquals($other->getTo()) ? $this->getTo() : $other->getTo()
		);
	}
	
	/**
	 * Gets the range as an array of which the first element is the start date
	 * string and the second, the end date string.
	 * 
	 * @param string $format
	 * @return string[]
	 */
	public function toStringArray($format = 'Y-m-d') {
		return array($this->getFrom()->format($format), $this->getTo()->format('Y-m-d'));
	}
	
	/**
	 *
	 * @param mixed $range
	 * @return DateRange
	 */
	public static function parseRange($range) {
		if ($range instanceof DateRange) {
			return $range;
		} else if (is_array($range) && count($range) == 2) {
			return new DateRange($range);
		} else {
			throw new IllegalArgumentException();
		}
	}
	
	public function __toString() {
		return 'DateRange[' . $this->getFrom()->format('Y-m-d e') . ', ' 
				. $this->getTo()->format('Y-m-d e') . ']';
	}
	
	/**
	 *
	 * @param array $ranges
	 * @return array
	 */
	public static function parseRanges($ranges) {
		$r = array();
		foreach ($ranges as $range) {
			$r[] = self::parseRange($range);
		}
		return $r;
	}
}
