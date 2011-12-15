<?php

namespace eoko\util\date;

use DateTime;

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
			throw new IllegalArgumentException();
		}
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
}
