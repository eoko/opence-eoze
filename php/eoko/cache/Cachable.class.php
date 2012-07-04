<?php

namespace eoko\cache;

/**
 * @author eric
 */
interface Cachable {

	static function getCacheVersion();

	static function __set_state($cache);
}

abstract class CachableBase implements Cachable {

	public function getCacheVersion() {
		if (isset($this->cacheVersion)) {
			return $this->cachedVersion;
		} else if (property_exists($this, 'cachedVersion')) {
			$class = get_class($this);
			return $class::$cachedVersion;
		}
	}

	protected function setState($cache) {
		foreach ($cache as $k => $v) $this->$k = $v;
	}

	protected function cache($index = null) {
		Cache::cacheObject($this, $index);
	}

}

