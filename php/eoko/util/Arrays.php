<?php

namespace eoko\util;

class Arrays extends \ArrayHelper {

	/**
	 * Compares two array considered as hash map, that is the order
	 * of the elements is not taken into account.
	 * 
	 * This method will return `true` if the two array contains the 
	 * same number of elements, the same keys, and the key are 
	 * associated to the same values (or, also, if the two variables 
	 * to compare are `null`).
	 * 
	 * @param array $left  
	 * @param array $right 
	 * @param bool $strict Use the strict comparison operator (`===`) 
	 * to evaluate equality between elements.
	 * @return type 
	 */
	public static function compareMap(array $left = null, array $right = null, $strict = true) {
		if ($left === null || $right === null) {
			return $left === $right;
		}
		if (self::isAssoc($left) && !self::isAssoc($right)) {
			return false;
		}
		if ($strict) {
			return self::orderMapAs($left, $right) === $right;
		} else {
			return self::orderMapAs($left, $right) == $right;
		}
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
