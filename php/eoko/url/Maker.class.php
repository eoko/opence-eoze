<?php

namespace eoko\url;

use eoko\util\Arrays;

const BASE_URL = \SITE_BASE_URL;

class Maker {

	private function __construct() {}
	
	private static $extra = null;
	
	public static function getFor($controller, $action = null, $params = null, $anchor = null) {
		
		$parts = array();
		
		if (is_array($controller)) {
			extract($controller);
		}
		
		if ($controller !== null) {
			$parts[] = "controller=$controller";
		}
		
		if ($action !== null) $parts[] = "action=$action";

		if ($params !== null) {
			foreach ($params as $k => $v) $parts[] = "$k=$v";
		}
		
		$parts = implode('&', $parts);
		
		if ($anchor !== null) {
			$anchor = "#$anchor";
		}
		
		return self::makeAbsolute(
//			urlencode(
				"index.php?$parts$anchor"
//			)
		);
	}
	
	public static function makeAbsolute($url) {
		if (substr($url, 0, 7) === 'http://') return $url;
		else return BASE_URL . $url;
	}
	
	public static function getExtra() {
		$extra = self::$extra;
		self::$extra = null;
		return $extra;
	}
}