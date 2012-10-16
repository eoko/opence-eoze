<?php

namespace eoko\plugin;

use eoko\util\Files;

/**
 * @todo Build a senseful PluginManager
 */
class PluginManager {

	private static $plugins = array();

//	private static $eventListeners = array();

	public static function init() {
		foreach (Files::listDirs(PHP_PATH . 'eoko', true, '/^[^.]/', false) as $dir) {
			if (file_exists($path = $dir . DS . 'plugin.init.php')) {
				$r = require_once $path;
				if (isset($r) && $r instanceof Plugin) {
					self::$plugins[] = $r;
					$r->init();
				}
			}
		}
	}

//	public static function on($class, $evt, $callback) {
//		self::$eventListeners[$class][$evt][] = $callback;
//	}
//
//	public static function fire($class, $evt, $args = array()) {
//		if (is_object($class)) $class = get_class($class);
//		if (isset(self::$eventListeners[$class][$evt])) {
//			foreach (self::$eventListeners[$class][$evt] as $listener) {
//				call_user_func_array($listener, $args);
//			}
//		}
//	}
}