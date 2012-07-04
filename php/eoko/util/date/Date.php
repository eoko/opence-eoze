<?php

namespace eoko\util\date;

use DateTime,
    DateTimeZone;
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
	 * Parses a Date object.
	 * 
	 * If the argument is a Date or DateTime, a whole new one will be created from the
	 * object's data, in order to workaround early PHP 5.3 versions buggy behaviour
	 * with DateTime...
	 * 
	 * @param DateTime|Date|string $date
	 * 
	 * @return Date
	 */
	public static function parseDate($date) {
		// clone propagates PHP bugs with DateTime (in early PHP 5.3 versions)
		// so we must create a whole new object from string
		if ($date instanceof DateTime) {
			return new Date($date->format('Y-m-d'), $date->getTimezone());
		} else if (is_string($date)) {
			return new Date($date, DefaultTimeZone::get());
		} else {
			throw new IllegalArgumentException("$date (" . gettype($date) . ')');
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
			parent::__construct($date, DefaultTimeZone::get());
		}
	}
	
	public function __toString() {
		return $this->format('Y-m-d');
	}

	public function equals($d) {
		$d = self::parseDate($d);
		$d->setTimezone($d->getTimezone());
		return $this->format('Ymd') === $d->format('Ymd');
	}
	
	public function afterOrEquals($d) {
		$d = self::parseDate($d);
		return !$d->diff($this)->invert || $this->equals($d);
	}
	
	public function beforeOrEquals($d) {
		$d = self::parseDate($d);
		return !$this->diff($d)->invert || $this->equals($d);
	}
	
	public function after($d) {
		$d = self::parseDate($d);
		return !$d->diff($this)->invert && !$this->equals($d);
	}
	
	public function before($d) {
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
