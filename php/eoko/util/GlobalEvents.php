<?php

namespace eoko\util;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 1 déc. 2011
 */
class GlobalEvents {

	private static $listeners;

	private function __construct() {}

	public static function addListener($class, $event, $listener) {
		if (is_object($class)) {
			$class = get_class($class);
		}
		self::$listeners[$class][$event][] = $listener;
	}

	public static function fire($class, $event, $_ = null) {
		if (is_object($class)) {
			$class = get_class($class);
		}
		if (isset(self::$listeners[$class][$event])) {
			$args = array_slice(func_get_args(), 2);
			foreach (self::$listeners[$class][$event] as $listener) {
				call_user_func_array($listener, $args);
			}
		}
	}
}
