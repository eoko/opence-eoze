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
	
	public static function extend(array $parent = null, array $config = null) {
		if (!$parent) {
			if ($config) {
				return $config;
			} else {
				return array();
			}
		} else if (!$config) {
			return $parent;
		}
		self::extendImpl($parent, $config);
		return $parent;
	}
	
	private static function extendImpl(&$parent, $config) {
		if (!$parent) {
			$parent = $config;
			return;
		}
		if (is_array($parent)) {
			if (Arrays::isAssoc($parent)) {
				if (is_array($config)) {
					foreach ($config as $key => $value) {
						if (substr($key, -2) === '[]') {
							$key = substr($key, 0, -2);
							if (!is_array($value)) {
								$parent[$key][] = $value;
							} else if (!array_key_exists($key, $parent)) {
								$parent[$key] = $value;
							} else {
								$parent[$key] = array_merge($parent[$key], $value);
							}
						} else if (array_key_exists($key, $parent)) {
							self::extendImpl($parent[$key], $value);
						} else {
							$parent[$key] = $value;
						}
					}
				} else if ($config === null) {
					// erasing parent array
					$parent = null;
				} else {
					throw new IllegalArgumentException();
				}
				return;
			}
		}
		if (!is_array($config) || !Arrays::isAssoc($config)) {
			// overriding scalar key
			$parent = $config;
			return;
		}
		throw new IllegalArgumentException();
	}
	
	public static function complement(array $config = null, array $parent = null) {
		throw new NotImplementedYetException();
	}
}
