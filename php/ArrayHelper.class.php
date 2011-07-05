<?php

class ArrayHelper {

	public static function isAssoc(array $arr) {
		return !(array_values($arr) === $arr);
	}

	/**
	 * Tests whether the passed $arr argument is an array and, in case it is,
	 * if it is an associative array.
	 * @param mixed $arr
	 * @return boolean 
	 * @see isAssoc()
	 */
	public static function isAssocArray($arr) {
		return is_array($arr) && self::isAssoc($arr);
	}

	public static function apply(&$obj = null, $vals, $maxRecursionLevel = 0, $applyNumericalIndexArray = false) {
		if ($vals === null) return $obj;
		if ($obj === null) $obj = array();
		return self::applyReccursive($obj, $vals, $maxRecursionLevel, 1, $applyNumericalIndexArray);
//		if ($vals === null) return $obj;
//		if ($obj === null) $obj = array();
//		foreach ($vals as $k => $v) {
//			$obj[$k] = $v;
//		}
//		return $obj;
	}

	public static function applyIf(&$obj = null, $vals, $maxRecursionLevel = true, $applyNumericalIndexArray = false) {
		if ($vals === null) return $obj;
//		if ($obj === null) $obj = array();
		if ($obj === null) return $obj = $vals;
		return self::applyIfReccursive($obj, $vals, $maxRecursionLevel, 1, $applyNumericalIndexArray);
	}

	public static function applyExtra(&$obj, $vals, $maxRecursionLevel = false, $applyNumericalIndexArray = false) {
		if ($vals === null) return $vals;
		if ($obj === null) $obj = array();
		return self::applyExtraReccursive($obj, $vals, $maxRecursionLevel, $applyNumericalIndexArray);
	}

	/**
	 *
	 * @param <type> $obj
	 * @param <type> $val
	 * @param <type> $recMax		exclusive
	 * @param <type> $recCurrent
	 * @return <type>
	 */
	protected static function applyIfReccursive(&$obj, $vals, $recMax, $recCurrent, $applyNumericalIndexArray = false) {
		foreach ($vals as $k => $v) {
			if (!isset($obj[$k])) {
				$obj[$k] = $v;
			} else if (($recMax === false || $recCurrent < $recMax) && is_array($obj[$k])) {
				if ($applyNumericalIndexArray || self::isAssoc($v)) {
					self::applyIfReccursive($obj[$k], $v, $recMax, $recCurrent+1);
				}
			}
		}
		return $obj;
	}

	protected static function applyReccursive(&$obj, $vals, $recMax, $recCurrent, $applyNumericalIndexArray = false) {
		foreach ($vals as $k => $v) {
			if (!isset($obj[$k])) {
				$obj[$k] = $v;
			} else if (is_array($v) && ($recMax === false || $recCurrent < $recMax) && is_array($obj[$k])) {
				if ($applyNumericalIndexArray || self::isAssoc($v)) {
					self::applyReccursive($obj[$k], $v, $recMax, $recCurrent+1);
				} else {
					$obj[$k] = $v;
				}
			} else {
				$obj[$k] = $v;
			}
		}
		return $obj;
	}

	/**
	 *
	 * @param <type> $obj
	 * @param <type> $val
	 * @param <type> $recMax		exclusive
	 * @param <type> $recCurrent
	 * @return <type>
	 */
	protected static function applyExtraReccursive(&$obj, $vals, $recMax, $recCurrent, $applyNumericalIndexArray = false) {

		foreach ($vals as $k => $v) {
			if (!isset($obj[$k])) {
				$obj[$k] = $v;
			} else if (($recMax === false || $recCurrent < $recMax)) {
				if (is_array($obj[$k]) && ($applyNumericalIndexArray || self::isAssoc($v))) {
					self::applyExtraReccursive($obj[$k], $v, $recMax, $recCurrent+1);
				} else {
					throw new IllegalStateException("Already set: $k");
				}
			} else {
				throw new IllegalStateException("The maximum reccursivity level "
						. "($recMax) prevents some conflict resolution");
			}
		}
		return $obj;
	}

	public static function array_applyIf(array &$obj, $vals) {
		if ($vals === null) return $vals;
		foreach ($vals as $k => $v) {
			if (!array_key_exists($k, $obj)) $obj[$k] = $v;
		}
		return $obj;
	}

	public static function chooseAs($src, $mapping) {
		$r = array();
		foreach ($mapping as $srcKey => $destKey) {
			if (isset($src[$srKey]))
				$r[$destKey] = $src[$srcKey];
		}
		return $r;
	}

	public static function pluck(array $obj, $key, $require = false) {
		$r = array();
		foreach ($obj as $o) {
			if (array_key_exists($key, $o)) {
				$r[] = $o[$key];
			} else if ($require) {
				throw new IllegalArgumentException("Missing key: $key");
			}
		}
		return $r;
	}
	
	public static function get($array, $key, $default = null) {
		if (isset($array[$key])) return $array[$key];
		else return $default;
	}

	public static function pick(&$obj, $key, $require = false, $unset = true) {
		if (!isset($obj[$key])) {
			if (!$require) return null;
			else throw new IllegalStateException("Missing key: \$obj[$key]");
		} else {
			$r = $obj[$key];
			if ($unset) unset($obj[$key]);
			return $r;
		}
	}

	public static function pickOneOf(&$obj, $keys, $require = false, 
			$clearAll = true, $checkIntegrity = true) {

		foreach ($keys as $key) {
			if (isset($obj[$key])) {

				$r = $obj[$key];
				unset($obj[$key]);

				if ($checkIntegrity || $clearAll) {
					unset($keys[$key]);
					foreach ($keys as $otherKey) {
						if (isset($obj[$otherKey])) {
							if ($checkIntegrity && $obj[$otherKey] !== $r)
								throw new IllegalStateException("\$obj[$key] !== \$obj[$otherKey]");

							if ($clearAll) unset($obj[$otherKey]);
						}
					}
				}

				return $r;
			}
		}
		
		if (!$require) return null;
		else throw new IllegalStateException("Missing key: \$obj[$key]");
	}

	public static function array_pick(array &$array, $key) {
		if (!array_key_exists($val, $obj))
				return null;
		else {
			$r = $array[$key];
			unset($array[$key]);
			return $r;
		}
	}

	public static function findKeyIndex($array, $key) {
		$i = 0;
		foreach ($array as $k => $v) {
			if ($k === $key) return $i;
			$i++;
		}
		return -1;
	}

	/**
	 * Compare 2 associative arrays.
	 * @param array $a
	 * @param array $b
	 * @return bool TRUE if the two arrays are the same, that is if they contains
	 * the same number of values, each key-value pair are the same and in the
	 * same order; else this method return false.
	 */
	public static function compareAssoc(array $a = null, array $b = null) {
		if ($a === null) {
			return $b === null;
		} else if ($b === null) {
			return false;
		} else if (count($a) !== count($b)) {
			return false;
		} else {
			foreach ($b as $k => $v) {
				if (!isset($a[$k]) || $a[$k] !== $v) return false;
			}
			return true;
		}
	}
}