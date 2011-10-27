<?php

namespace eoze\util;

use ReflectionClass;

/**
 * Utility class providing helper methods to get informations about classes,
 * mostly by using reflection.
 * 
 * All methods of this class that take an argument $class accept either a
 * string representing the class name, or a {@link ReflectionClass} object,
 * or an object which class will be extracted with {@link get_class()}.
 * 
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 27 oct. 2011
 */
class Classes {
	
	private function __construct() {}
	
	private static function toReflectionClass(&$class) {
		if ($class instanceof ReflectionClass) {
			$rc = $class;
			$class = $class->name;
			return $rc;
		} else {
			if (is_object($class)) {
				$class = get_class($class);
			}
			return new ReflectionClass($class);
		}
	}

	/**
	 * Gets the names of all the parent classes of the given $class.
	 * 
	 * @param string|Object|ReflectionClass $class
	 * @param bool $includeSelf TRUE to include the name of the explored $class in the
	 * returned array.
	 * @return array An array containing the names of all the parent classes as strings.
	 */
	public static function getParentNames($class, $includeSelf = false) {
		$rc = self::toReflectionClass($class);
		$r = $includeSelf ? array($class) : array();
		while ($rc = $rc->getParentClass()) {
			$r[] = $rc->getName();
		}
		return $r;
	}
	
	/**
	 * Gets the names of all the interfaces that are implemented by the given $class,
	 * and also the names of all its parent class.
	 * 
	 * @param string|Object|ReflectionClass $class
	 * @param bool $includeSelf TRUE to include the name of the explored $class in the
	 * returned array.
	 * @return array An array containing the names of all implemented interfaces as strings.
	 */
	public static function getImplementedInterfaces($class, $includeSelf = false) {
		$rc = self::toReflectionClass($class);
		$r = $includeSelf ? array($class) : array();
		$r = array_merge($r, $rc->getInterfaceNames());
		$r = array_merge($r, self::getParentNames($class));
		return $r;
	}
}
