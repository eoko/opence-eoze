<?php

namespace eoko\options;

class Options {
	
	private function __construct($opts = null) {
		if ($opts !== null) {
			foreach ($opts as $name => $v) {
				$this->$name = $opts[$name];
			}
		}
	}
	
	public static function apply($target, $opts) {
		if ($opts === null) {
			return;
		} else {
			foreach ($opts as $k => $v) {
				$target->$k = $v;
			}
		}
	}
	
	public static function parse($opts) {
		$class = get_called_class();
		if ($opts === null || is_array($opts)) {
			return new $class();
		} else if ($opts instanceof Options) {
			if (is_a(get_class($opts), $class)) {
//				if (!$copy) {
//					return $opts;
//				} else {
//					// if $src is a subclass of self, we take all props from $src
					return new $class($opts);
//				}
			} else {
				// if $src is not a subclass of self, we take only props
				// from $src that exist in self
				$o = new $class();
				foreach ($o as $k => $v) {
					if (property_exists($opts, $k)) {
						$o->$k = $opts->$k;
					}
				}
				return $o;
			}
		}
	}
	
	public static function create() {
		$class = \get_called_class();
		return new $class();
	}
	
	public function __call($name, $args) {
		$this->$name = $args[0];
		return $this;
	}
	
	public static function __callStatic($name, $args) {
		return self::create()->__call($name, $args);
	}
	
//	const B1	= 1;
//	const B2	= 2;
//	const B3	= 4;
//	const B4	= 8;
//	const B5	= 16;
//	const B6	= 32;
//	const B7	= 64;
//	const B8	= 128;
//	const B9	= 256;
//	const B10	= 512;
//	const B11	= 1024;
//	const B12	= 2048;
//	const B13	= 4096;
//	const B13	= 8192;
//	const B14	= 16384;
//	const B15	= 32758;
//	const B16	= 65536;
}