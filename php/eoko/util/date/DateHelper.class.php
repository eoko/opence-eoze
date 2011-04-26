<?php

namespace eoko\util\date;

use eoko\util\Operator;
use DateTime;
use DateTimeZone;

class DateHelper {
	
	private static $defaultTimeZone = 'UTC';
	
	private static $defaultFormat = array(
		'Y-m-d H:i:m', 'Y-m-d H:i', 'Y-m-d'
	);
	
	private $timeZone;
	private $format;
	
	private $defaultHelper = null;
	
	private function __construct($dateFormat = null, $timeZone = null) {
		// time zone
		if ($timeZone === null) {
			$timeZone = self::$defaultTimeZone;
		}
		$this->timeZone = self::parseDateTimeZone($timeZone);
		// format
		if ($dateFormat === null) {
			$this->format = self::$defaultFormat;
		}
	}
	
	/**
	 * @return DateHelper 
	 */
	private static function getDefaultHelper() {
		if (!self::$defaultHelper) {
			self::$defaultHelper = new DateHelper();
		}
		return self::$defaultHelper;
	}
	
	/**
	 * @return DateHelper 
	 */
	public function getHelper() {
		if (isset($this)) return $this;
		else return self::getDefaultHelper();
	}

	/**
	 * @param DateTimeZone|string $timeZone
	 * @return DateTimeZone
	 */
	public static function parseDateTimeZone($timeZone) {
		if ($timeZone === null) {
			$timeZone = self::$defaultTimeZone;
		}
		if ($timeZone instanceof DateTimeZone) {
			return $timeZone;
		} else if (is_string($timeZone)) {
			return new DateTimeZone($timeZone);
		} else {
			throw new \IllegalArgumentException('$timeZone');
		}
	}

	/**
	 * @param DateTime|string $date
	 * @return DateTime
	 */
	public function parseDateTime($date) {
		if (isset($this)) {
			return self::doParseDateTime($date, $this->format, $this->timeZone);
		} else {
			return self::doParseDateTime($date, $format, $timeZone);
		}
	}
	
	private static function doParseDateTime($date, $format = null, $timeZone = null) {
		$timeZone = self::parseDateTimeZone($timeZone);
		if ($format === null) $format = self::$defaultFormat;
		// (...)
		if (func_num_args() > 1) {
			$r = array();
			foreach (func_get_args() as $date) {
				$r[] = self::doParseDateTime($date, $format, $timeZone);
			}
			return $r;
		}
		// ($date)
		if ($date instanceof DateTime) {
			return $date;
		} else {
			if (is_array($format)) {
				foreach ($format as $format) {
					$dt = DateTime::createFromFormat($format, $date, $timeZone);
					if ($dt) return $dt;
				}
				// default (will fail, that's on purpose)
				return new DateTime($date, $timeZone);
			} else {
				return DateTime::createFromFormat($format, $date, $timeZone);
			}
		}
	}
	
	public function equals($d1, $d2) {
		list($d1, $d2) = isset($this) ? $this->parseDateTime($d1, $d2)
				: self::parseDateTime($d1, $d2);
		$diff = $d1->diff($d2);
		if ($diff->days === false) {
			$days = $diff->y === 0
					&& $diff->m === 0
					&& $diff->d === 0;
		} else {
			$days = $this->days === 0;
		}
		return $days
				&& $diff->h === 0
				&& $diff->i === 0
				&& $diff->s === 0;
	}
	
	public function moreThan($d1, $d2) {
		$me = isset($this) ? $this : $this->getDefaultHelper();
		list($d1, $d2) = $me->parseDateTime($d1, $d2);
		return !!$me->moreThanOrEquals($d1, $d2)
				&& !$me->equals($d1, $d2);
	}
	
	public function moreThanOrEquals($d1, $d2) {
		list($d1, $d2) = isset($this) ? $this->parseDateTime($d1, $d2)
				: self::parseDateTime($d1, $d2);
		$diff = $d1->diff($d2);
		return $diff->invert === 0;
	}
	
	public function lessThanOrEquals($d1, $d2) {
		$me = isset($this) ? $this : $this->getDefaultHelper();
		list($d1, $d2) = $me->parseDateTime($d1, $d2);
		$diff = $d1->diff($d2);
		return $diff->invert === 1
				|| ($me->dateEquals($d1, $d2));
	}
	
	public function lessThan($d1, $d2) {
		list($d1, $d2) = isset($this) ? $this->parseDateTime($d1, $d2)
				: self::parseDateTime($d1, $d2);
		$diff = $d1->diff($d2);
		return $diff->invert === 1;
	}
	
	public function compare($d1, $d2, $operator = Operator::EQUAL) {
		$me = isset($this) ? $this : $this->getDefaultHelper();
		switch ($operator) {
			case Operator::EQUAL: return $me->equals($d1, $d2);
			case Operator::MORE: return $me->moreThan($d1, $d2);
			case Operator::MORE_OR_EQUAL: return $me->moreThanOrEquals($d1, $d2);
			case Operator::LESS: return $me->lessThan($d1, $d2);
			case Operator::LESS_OR_EQUAL: $me->lessThanOrEquals($d1, $d2);
			default: throw new \IllegalArgumentException('$operator');
		}
	}
	
}
