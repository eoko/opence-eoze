<?php

namespace eoko\php\generator;

use eoko\template\PHPCompiler;

class ClassGeneratorManager {

	private static $generators = null;
	private static $aliases = null;

	private function __construct() {}

	/**
	 *
	 * @param $generator
	 * @param string|array[string] $class
	 * @param string $namespace
	 * @param string|array[string] $alias will be ignored if $class is given as
	 * an array
	 */
	public static function register($generator, $class, $namespace = '\\', $alias = null) {

		if (substr($namespace, -1) !== '\\') $namespace .= '\\';

		if (is_array($class)) {

			$r = array();

			if (is_array($class)) foreach ($class as $alias => $class) {
				if (is_string($alias)) {
					// $k is an alias
					$r[] = self::register($generator, $class, $namespace, $alias);
				} else {
					$r[] = self::register($generator, $class, $namespace);
				}
			} else {
				$r[] = self::register($generator, $class, $namespace, $alias);
			}

			return $r;

		} else {
			$class = strtolower("$namespace$class");
			self::$generators[$class] = $generator;
			if ($alias !== null) {
				if (is_array($alias)) foreach ($alias as $alias) {
					self::$aliases[strtolower($alias)] = $class;
				} else {
					self::$aliases[strtolower($alias)] = $class;
				}
				return array($class, $alias);
			}
			return $class;
		}
	}

	public static function unregister($uid) {
		if (is_array($uid)) {
			unset(self::$generators[$uid[0]]);
			unset(self::$aliases[$uid[0]]);
		} else {
			unset(self::$generators[$uid]);
		}
	}

	public static function generate($class) {

		$class = strtolower($class);

		if (isset(self::$aliases[$class])) {
			$alias = $class;
			$class = self::$aliases[$class];
		}

		if (!isset(self::$generators[$class])) {
			return false;
		}

		$generator = self::$generators[$class];

		if ($generator instanceof ClassGenerator) {
			$r = $generator->generateClass($class);
		} else if (is_callable($generator)) {
			$r = call_user_func($generator, $class);
		} else if (is_string($generator) && is_subclass_of($generator, 'ClassGenerator')) {
			$r = call_user_func(array($generator, 'generateClass'), $class);
		} else {
			throw new \IllegalStateException('Invalid generator type: ' . $generator);
		}

		if (isset($alias)) class_alias($class, $alias);

		if (null !== $r) {
			if ($r instanceof PHPCompiler) {
				$r->compile();
				\eoko\cache\Cache::cachePhpClass($class, "<?php\n$r");
			}
			return $r;
		} else {
			return true;
		}
	}
}
