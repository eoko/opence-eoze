<?php

namespace eoko\util;

class Arrays extends \ArrayHelper {
	
	public static function compareMap(array $left = null, array $right = null) {
		if ($left === null || $right === null) {
			return $left === $right;
		}
		if (self::isAssoc($left) && !self::isAssoc($right)) {
			return false;
		}
		return self::orderMapAs($left, $right) === $right;
	}
	
	public static function orderMapAs(array $map, array $format) {
		$return = array();
		foreach ($format as $key => $v) {
			if (array_key_exists($key, $map)) {
				if (self::isAssocArray($map[$key]) && self::isAssocArray($format[$key])) {
					$return[$key] = self::orderMapAs($map[$key], $format[$key]);
				} else {
					$return[$key] = $map[$key];
				}
				unset($map[$key]);
			}
		}
		foreach ($map as $key => $value) {
			$return[$key] = $value;
		}
		return $return;
	}
	
}