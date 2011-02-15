<?php

namespace eoko\util;

class Json {
	
	private static function utf8_encode_array($in) {
		$out = array();
        if (is_array($in)) {
			foreach ($in as $key => $value) {
				$out[utf8_encode($key)] = self::utf8_encode_array($value);
			}
		} else if (is_string($in)) {
			if (mb_detect_encoding($in) != "UTF-8")
				return utf8_encode($in);
			else
				return $in;
		} else {
			return $in;
		}
		return $out;
	}

	public static function encode($data) {
//		return urlencode(json_encode(self::utf8_encode_array($data)));
		return json_encode(self::utf8_encode_array($data));
	}

	public static function decode($json, $assoc = true) {
		return json_decode(urldecode($json), $assoc);
//		return json_decode($json, $assoc);
	}
}