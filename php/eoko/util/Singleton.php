<?php

namespace eoko\util;

abstract class Singleton {
	
	private static $instances = array();

	private function __construct() {}

	abstract protected function construct();
	
	protected static function createInstance() {
		$o = self::createObject();
		$o->construct();
	}

	final protected static function createObject() {
		$class = get_called_class();
		return new $class();
	}

//	public static function __callStatic($name, $args) {
//		$class = get_called_class();
//
//		if (!isset(self::$instances[$class])) {
//			self::$instances[$class] = $class::createInstance();
//		}
//
//		if (0 === $n = count($args)) {
//			return self::$instances[$class]->$name();
//		} else if (1 === $n = count($args)) {
//			return self::$instances[$class]->$name($args[0]);
//		} else if (2 === $n = count($args)) {
//			return self::$instances[$class]->$name($args[0], $args[1]);
//		} else if (3 === $n = count($args)) {
//			return self::$instances[$class]->$name($args[0], $args[1], $args[3]);
//		} else {
//			return call_user_func_array(array($class, $name), $args);
//		}
//	}
	
	public final static function getInstance() {
		$class = get_called_class();
		if (!isset(self::$instances[$class])) {
			return self::$instances[$class] = $class::createInstance();
		} else {
			return self::$instances[$class];
		}
	}
	
}