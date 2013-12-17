<?php

namespace eoko\util;

class Language {

	private function __construct() {}

	public static function typeof($var) {
		if (is_array($var)) {
			return 'array';
		} else if (is_string($var)) {
			return 'string';
		} else if (is_bool($var)) {
			return 'bool';
		} else if (is_int($var)) {
			return 'int';
		} else if (is_float($var)) {
			return 'float';
		} else if (is_object($var)) {
			return 'object';
		} else {
			return 'UNKNOWN';
		}
	}

	public static function varExportClean($var) {
		$r = str_replace("\n", ' ', var_export($var, true));
		while (strstr($r, '  ')) $r = str_replace('  ', ' ', $r);
		$r = str_replace('( ', '(', $r);
		$r = str_replace(' )', ')', $r);
		$r = str_replace(',)', ')', $r);
		$r = str_replace('array (', 'array(', $r);
		return $r;
	}
}