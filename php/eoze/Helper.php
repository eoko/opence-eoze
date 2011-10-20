<?php
/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 20 oct. 2011
 */

namespace eoze;

use IllegalArgumentException;

final class Helper {
	
	private function __construct() {}
	
	public static function parseInt($value, $require = false, $default = null) {
		if (is_int($value)) {
			return $value;
		} else if (preg_match('/^\s*(-?)\s*(\d+)\s*$/', $value, $m)) {
			return (int) "$m[1]$m[2]";
		} else if ($require) {
			throw new IllegalArgumentException("Cannot be parsed to an int: $value");
		} else {
			return $default;
		}
	}
	
	public static function isInt($value) {
		return self::parseInt($value) !== null;
	}
}
