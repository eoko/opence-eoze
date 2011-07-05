<?php
/**
 * @author Éric Ortéga <eric@mail.com>
 */

class DateHelper {

	public static function dateFR($time = null) {
		if ($time === null) $time = time();
		return dateFR($time);
	}

	public static function dateExtToSql($date) {
		if ($date == '') return null;
		// 07/04/1986 -> 1986-04-07
		return preg_replace('@(\d\d)\/(\d\d)\/(\d{4})@', '$3-$2-$1', $date);
	}

	public static function getDatetime($datetime, $format) {
		throw new \IllegalStateException('Not implemented');
	}

	const SQL_DATE			= 1;
	const SQL_DATETIME		= 2;
	const TIME				= 3;
	const DATE_LOCALE		= 4;
	const DATETIME_LOCALE	= 8;

	public static function getDateAs($date, $format = self::SQL_DATE) {
		if ($date === null) return null;
		switch ($format) {
			case self::SQL_DATE:
				return $date;
			case self::SQL_DATETIME:
				return $date . ' 00:00:00';
			case self::TIME: return strtotime($date);
			default:
				Logger::getLogger('DateHelper')->warn('Invalid getDateAs() format: {}', $format);
			case self::DATE_LOCALE:
				setlocale(LC_TIME, 'fr_FR');
				return strftime('%x', strtotime($date));
			case self::DATETIME_LOCALE:
				setlocale(LC_TIME, 'fr_FR');
				return strftime('%x %T', strtotime($date));
		}
	}

	public static function getDatetimeAs($datetime, $format = self::SQL_DATE) {
		if ($datetime === null) return null;
		switch ($format) {
			case self::SQL_DATE:
				return substr($datetime, 0, 10);
			case self::SQL_DATETIME:
				return $datetime;
			case self::TIME:
				return strtotime($datetime);
			case self::DATE_LOCALE:
				setlocale(LC_TIME, 'fr_FR');
				return strftime('%x', strtotime($datetime));
			default:
				Logger::getLogger('DateHelper')->warn('Invalid getDatetimeAs() format: {}', $format);
			case self::DATETIME_LOCALE:
				setlocale(LC_TIME, 'fr_FR');
				return strftime('%x %T', strtotime($datetime));
		}
	}

	public static function getTimeAs($time, $format = self::TIME) {
		if ($time === null) return null;
		switch ($format) {
			case self::SQL_DATE:
				return date("Y-m-d", $time);
			case self::SQL_DATETIME:
				return date("Y-m-d H:i:s", $time);
			default:
				Logger::getLogger('DateHelper')->warn('Invalid getDatetimeAs() format: {}', $format);
			case self::TIME:
				return $time;
			case self::DATE_LOCALE:
				setlocale(LC_TIME, 'fr_FR');
				return strftime('%x', $time);
			case self::DATETIME_LOCALE:
				setlocale(LC_TIME, 'fr_FR');
				return strftime('%x %T', $time);
		}
	}

}