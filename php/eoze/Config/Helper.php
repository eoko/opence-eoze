<?php

namespace eoze\Config;

use eoko\util\Arrays;

use eoze\Exception\NotImplementedYetException;

use IllegalArgumentException;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 29 oct. 2011
 */
class Helper {
	
	private function __construct() {}
	
	/**
	 * Extends the $parent array with the $child array.
	 * 
	 * As a general rules, values for keys existing only in the $parent or
	 * the $child array will be copied to the returned array, while values
	 * for keys existing in both array will be merged. The exact meaning
	 * of the merge operation depends on the types of the involved values.
	 * 
	 * - Scalar values in the child array will replace scalar values in the
	 *   parent array.
	 * 
	 * - Indexed arrays in the child array will replace indexed arrays in the
	 *   parent array.
	 * 
	 * - Associative arrays in the child array will be merged with associative
	 *   arrays in the parent array (with the same meaning as described here).
	 * 
	 * - Arrays (indexed or associative) cannot override scalar values, that
	 *   is if the parent array contains a scalar value for a given key and
	 *   the child array contains an array for the same key, an
	 *   IllegalArgumentException will be raised.
	 * 
	 * - In the same way, scalar values cannot override arrays (indexed or 
	 *   associative) and an IllegalArgumentException will be thrown in this
	 *   case.
	 * 
	 * <strong>The array operator []</strong>
	 * 
	 * Finally, the array operator [] can be used in key names to indicate
	 * that the values should be added to an indexed array.
	 * 
	 * E.g.
	 * <code>
	 * Helper::extend(array(
	 *     'expandedKey' => array(1,2,3)
	 * ), array(
	 *     'expandedKey[]' => array(2,4,6)
	 * ));
	 * // will return:
	 * array(
	 *   'expandedKey' => array(1,2,3,2,4,6)
	 * )</code>
	 * 
	 * Keys suffixed with the array operator [] will always be expanded, even
	 * if they exist only in the $parent or only in the $child array.
	 * 
	 * Converted values will always be indexed arrays, and the conflict rules
	 * explained above will apply (that is, if the $child array contains a key
	 * associated to an associative array or a scalar value, and that key 
	 * overrides an expanded key in the $parent array, then an 
	 * IllegalArgumentException will be raised).
	 * 
	 * If a key suffixed with the array operator is associated to a value that
	 * is not an indexed array (either a scalar value, or an associative
	 * array), then the value will be converted to an indexed array that will
	 * have the value as its unique element.
	 * 
	 * E.g.
	 * <code>
	 * array(
	 *     'expandedKey[]' => array('a' => 1, 'b' => 2)
	 * )
	 * // will be converted to:
	 * array(
	 *     'expandedKey' => array(
	 *         0 => array('a' => 1, 'b' => 2)
	 *     )
	 * )</code>
	 * 
	 * Notice that, when a key suffixed with the array operator [] is associated
	 * with a value that contains associative arrays, these arrays will 
	 * <strong>not</strong> be parsed, and so if they contains keys suffixed
	 * with the array operator, they will be left untouched.
	 * 
	 * E.g.
	 * <code>
	 * array(
	 *     'expandedKey[]' => array('a' => 1, 'b[]' => 2),
	 *     'expandedKey2[] => array(
	 *         array('aa' => 11, 'bb[]' => 22
	 *     )
	 * )
	 * // will be converted to:
	 * array(
	 *     'expandedKey' => array(
	 *         [0] => array('a' => 1, 'b[]' => 2)
	 *     ),
	 *     'expandedKey2 => array(
	 *         [0] => array('aa' => 11, 'bb[]' => 22)
	 *     )
	 * )</code>
	 * 
	 * @param array $parent the parent array
	 * @param array $child  the overriding array
	 * @return array        the extended array. This method always returns an
	 *                      array, event if both params are NULL.
	 * 
	 * @throws IllegalArgumentException
	 */
	public static function extend(array $parent = null, array $child = null) {
		if (!$parent) {
			if ($child) {
				return self::expandSpecialKeys($child);
			} else {
				return array();
			}
		} else if (!$child) {
			return self::expandSpecialKeys($parent);
		}
		return self::extendImpl($parent, $child, null);
	}
	
	private static function expandSpecialKeys($array) {
		$copy = array();
		foreach ($array as $key => $value) {
			if (substr($key, -2) === '[]') {
				$key = substr($key, 0, -2);
				if (!Arrays::isIndexedArray($value)) { // shortcut form
					$copy[$key][] = $value;
				} else {
					$copy[$key] = $value;
				}
			} else if (Arrays::isAssocArray($value)) {
				$copy[$key] = self::expandSpecialKeys($value);
			} else {
				$copy[$key] = $value;
			}
		}
		return $copy;
	}
	
	private static function extendImpl_applyAssocToAssoc(array &$parent, array $config) {
		foreach ($config as $key => $value) {
			if (substr($key, -2) === '[]') {
				$key = substr($key, 0, -2);
				if (isset($parent[$key])) {
					if (!is_array($parent[$key])) {
						throw new IllegalArgumentException(
								"Cannot apply the key {$key}[] to scalar value "
								. "'$parent[$key]'"
						);
					} else if (Arrays::isAssoc($parent[$key])) {
						throw new IllegalArgumentException(
								"Cannot apply the key {$key}[] to associative array"
						);
					}
				}
				if (!Arrays::isIndexedArray($value)) {
					// append to indexed array (created if needed)
					$parent[$key][] = $value;
				} else if (!array_key_exists($key, $parent)) {
					// create the indexed array in the returned array
					$parent[$key] = $value;
				} else {
					// merge into existing indexed array
					$parent[$key] = array_merge($parent[$key], $value);
				}
			} else if (array_key_exists($key, $parent)) {
				self::extendImpl($parent[$key], $value, $key);
			} else {
				$parent[$key] = $value;
			}
		}
	}
	
	private static function extendImpl(&$parent, $config, $rootKey) {
		
		if (!$parent) {
			return $parent = $config;
		}
		
		if (is_array($parent)) { // --- $parent is an array ----------------------------------------
			
			if (Arrays::isAssoc($parent)) { // $parent is an associative array
				
				$parent = self::expandSpecialKeys($parent);
				
				if (is_array($config)) { // $config is array
					
					if (Arrays::isAssoc($config)) { // $config is an associative array
						self::extendImpl_applyAssocToAssoc($parent, $config);
					} else { // $config is an indexed array
						throw new IllegalArgumentException(
							"Indexed array cannot override associative array for key '$rootKey'"
						);
					}
				} else { // $config is scalar

					if ($config === null) {
						// erasing parent array
						$parent = null;
					} else {
						throw new IllegalArgumentException(
							"Scalar value '$config' cannot override associative array for key "
								. "'$rootKey'"
						);
					}
				}
			
			} else { // $parent is an indexed array

				if ($config === null) {
					$parent = null;
				} else if (is_array($config)) {
					if (!Arrays::isAssoc($config)) {
						// An indexed array overrides (replaces) an existing one
						$parent = $config;
					} else {
						// TODO test
						throw new IllegalArgumentException(
							"Associative array cannot override indexed array for key '$rootKey'"
						);
					}
				} else { // $config is scalar
					throw new IllegalArgumentException(
						"Scalar value '$config' cannot override indexed array for key '$rootKey'"
					);
				}
			}
		
		} else { // --- $parent is scalar ----------------------------------------------------------
			
			if (!is_array($config)) {
				// overriding scalar key
				$parent = $config;
			} else { // $config is an array
				if (!Arrays::isAssoc($config)) {
					// config is an indexed array
					throw new IllegalArgumentException(
						"Indexed array cannot override scalar value '$parent' for key '$rootKey'"
					);
				} else {
					throw new IllegalArgumentException(
						"Associative array cannot override scalar value '$parent' for key "
						. "'$rootKey'"
					);
				}
			}
		}
		
		return $parent;
	}
	
	public static function complement(array $child = null, array $parent = null) {
		throw new NotImplementedYetException();
	}
}
