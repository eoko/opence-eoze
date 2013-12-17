<?php

namespace eoko\cache;

use eoko\util\Singleton;

use UnsupportedOperationException;
use Logger;

abstract class CachableSingleton extends Singleton {

	private static $instance = null;

	protected static function createInstance() {
		if (!self::$instance) {
			$class = get_called_class();
			Logger::get($class)->startTimer('CREATE', $class . 'singleton created in {}');
			if ((null === $o = Cache::getCachedSingleton($class, $class::getCacheVersion()))) {
				$o = self::createObject();
				$o->construct();
				$o->cache();
			}
			Logger::get($class)->stopTimer('CREATE');
		}
		return $o;
	}

	protected static function getCacheVersion() {
		if (property_exists($class = get_called_class(), 'cacheVersion')) {
			return $class::$cacheVersion;
		} else {
			throw new UnsupportedOperationException(
				'Missing cache version in ' . get_called_class()
			);
		}
	}

	public final static function __set_state($cache) {
		$class = get_called_class();
		$o = new $class(false);
		$o->setState($cache);
		return $o;
	}

	protected function setState($cache) {
		foreach ($cache as $k => $v) {
			$this->$k = $v;
		}
	}

	private function cache() {
		return Cache::cacheSingleton($this, $this->getCacheVersion());
	}
}