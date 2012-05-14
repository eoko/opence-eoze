<?php

namespace eoko\util\date;

use DateTime;
use ParseRange;
use IllegalArgumentException;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 15 déc. 2011
 */
class Date extends DateTime{
	
	/**
	 * @param DateTime|string $d
	 * @return Date
	 */
	public static function parseDate($d) {
		if ($d instanceof Date) {
			return $d;
		} else if ($d instanceof DateTime) {
			return new Date($d->format('Y-m-d'), $d->getTimezone());
		} else if (is_string($d)) {
			return new Date($d);
		} else {
			throw new IllegalArgumentException("$d (" . gettype($d) . ')');
		}
	}
	
	public function __construct($date, $timeZone = null) {
		if ($date instanceof DateTime) {
			$date = $date->format('Y-m-d');
		}
		if ($timeZone !== null) {
			parent::__construct($date, $timeZone);
		} else {
			// DateTime constructor won't accept NULL as a valid param for $timeZone...
			parent::__construct($date);
		}
	}
	
	public function __toString() {
		return $this->format('Y-m-d');
	}

	public function equals(DateTime $d) {
		$d = clone $d;
		$d->setTimezone($d->getTimezone());
		return $this->format('Ymd') === $d->format('Ymd');
	}
	
	public function afterOrEquals(DateTime $d) {
		return !$d->diff($this)->invert || $this->equals($d);
	}
	
	public function beforeOrEquals(DateTime $d) {
		return !$this->diff($d)->invert || $this->equals($d);
	}
	
	public function after(DateTime $d) {
		return !$d->diff($this)->invert && !$this->equals($d);
	}
	
	public function before(DateTime $d) {
		$d = self::parseDate($d);
		return $d->after($this);
	}
	
	public function inRanges(array $ranges) {
		foreach ($ranges as $range) {
			$range = DateRange::parseRange($range);
			if ($this->afterOrEquals($range->getFrom()) 
					&& $this->beforeOrEquals($range->getTo())) {
				return true;
			}
		}
		return false;
	}
}
