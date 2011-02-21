<?php

namespace eoko\util\collection;

class ImmutableMap implements Map {
	
	private $array;
	
	/**
	 * If this option is specified, then an exception will be raised when trying
	 * to access a key that doesn't exists, else NULL will be returned in that
	 * case. Default is OFF.
	 */
	const REQUIRE_KEYS = 1;
	
	const WRAP_CHILD_ARRAYS_OFF = 2;
	
	private $require = false;
	private $wrapChildren = true;
	
	private $cache = null;
	
	public function __construct(array &$array = null, $opts = null) {
		$this->array = $array !== null ? $array : array();
		$this->require = $opts & self::REQUIRE_KEYS;
		$this->wrapChildren = !($opts & self::WRAP_CHILD_ARRAYS_OFF);
	}
	
	public function count() {
		return count($this->array);
	}
	
	public function __get($k) {
		if (isset($this->cache[$k])) {
			return $this->cache[$k];
		} else if (array_key_exists($k, $this->array)) {
			if ($this->wrapChildren && is_array($this->array[$k])) {
				return $this->cache[$k] = new ImmutableMap($this->array[$k]);
			} else {
				return $this->array[$k];
			}
		} else if ($this->require) {
			throw new InvalidOffsetException('Undefined key: ' . $k);
		} else {
			return null;
		}
	}

	/**
	 * @return array
	 */
	public function toArray() {
		return $this->array;
	}
}

