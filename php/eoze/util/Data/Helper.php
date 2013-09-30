<?php

namespace eoze\util\Data;

use IllegalArgumentException;

/**
 *
 * @todo This class is a work in progress!
 * 
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 28 oct. 2011
 */
class Helper {

	private function __construct() {}

	/**
	 * Expands an array by transforming its keys that contain a $separator
	 * into nested sub arrays.
	 * 
	 * E.g.
	 * <code>
	 * array('my.expanding.key' => 'myValue')
	 * // is transformed to:
	 * array(array('my' => array('expanding' => array('key' => 'myValue'))));
	 * </code>
	 * 
	 * @param array $array
	 * @return array 
	 */
	public static function expand(array $array = null, $separator = '.') {
		throw new \UnsupportedOperationException(get_class() . '::expand()');
		return self::expandImpl($array, $separator);
	}

	private static function expandImpl(array $array = null, $separator = '.', $prevKey = null) {
		if (!$array) {
			return $array;
		}
		$orig = $array;
		$return = array();
		foreach ($orig as $k => $v) {
			if (count($parts = explode($separator, $k)) > 1) {
				$lastKey = array_pop($parts);
				$pointer =& $return;
				foreach ($parts as $subKey) {
					if (!array_key_exists($subKey, $pointer)) {
						 $pointer[$subKey] = array();
					} else if (!is_array($pointer)) {
						throw new IllegalArgumentException("Conflict in expanding key: $prevKey$k");
					}
					$pointer =& $pointer[$subKey];
				}
				self::expand_ApplyValue($pointer, $lastKey, $v, $separator, "$prevKey$k");
			} else {
				self::expand_ApplyValue($return, $k, $v, $separator, "$prevKey$k");
			}
		}
		return $return;
	}

	private static function expand_ApplyValue(&$array, $key, $value, $separator, $fullKey) {
		if (!is_array($array)) {
			throw new IllegalArgumentException("Conflict in expanding key: $fullKey");
		} 
		if (is_array($value)) {
			$value = self::expandImpl($value, $separator, "$key.");
			foreach ($value as $k => $v) {
				if (is_array($v)) {
					if (!array_key_exists($key, $array)) {
						$array[$key] = array();
					} else if (!is_array($array[$key])) {
						throw new IllegalArgumentException(
								"Conflict in expanding key: $fullKey\[$key.$k]");
					}
					self::expand_ApplyValue($array[$key], $k, $v, $separator, "$fullKey\[$key.$k]");
				} else {
					$array[$key][$k] = $v;
				}
			}
//			foreach ($value as $k => $v) {
//				if (is_array($v)) {
//					foreach ($v as $kk => $vv) {
//						$array[$key][$k][$kk] = $vv;
//					}
//				} else
//				$array[$key][$k] = $v;
//			}
		} else if (array_key_exists($key, $array)) {
			throw new IllegalArgumentException("Conflict in aggregating key: $fullKey");
		} else {
			$array[$key] = $value;
		}
	}

	public static function node(array $array = null, $key, &$value = null) {
		if ($array === null) {
			return false;
		}
		$parts = explode('.', $key);
		$node = $array;
		foreach ($parts as $k) {
			if (is_array($node) && array_key_exists($k, $node)) {
				$node = $node[$k];
			} else {
				return false;
			}
		}
		$value = $node;
		return true;
	}
}
