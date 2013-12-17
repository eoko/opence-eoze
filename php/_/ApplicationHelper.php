<?php

class ApplicationHelper {

	const TS_DAY		= 5;
	const TS_HOUR		= 6;
	const TS_MINUTE		= 7;
	const TS_SECOND		= 8;

	public static function makeTimestamp($precision = self::TS_SECOND) {
		switch ($precision) {
			case self::TS_DAY: return date("Ymd");
			case self::TS_HOUR: return date("YmdH");
			case self::TS_MINUTE: return date("YmdHi");
			default:
			case self::TS_SECOND: return date("YmdHis");
		}
	}
}