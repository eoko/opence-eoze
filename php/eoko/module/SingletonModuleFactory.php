<?php

namespace eoko\module;

abstract class SingletonModuleFactory implements ModuleFactory {

	private static $instances = null;

	private function __construct() {
		$this->construct();
	}

	protected function construct() {}

	public static function get() {
		$class = get_called_class();
		if (isset(self::$instances[$class])) return self::$instances[$class];
		else return self::$instances[$class] = new $class();
	}

}
