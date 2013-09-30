<?php

class DateTimeUTC extends DateTime {

	public static $utcTimeZone;

	public function __construct($time = "now") {
		parent::__construct($time, self::$utcTimeZone);
	}
}

DateTimeUTC::$utcTimeZone = new DateTimeZone('UTC');
