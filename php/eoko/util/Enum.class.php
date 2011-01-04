<?php

namespace eoko\util;
use \IllegalArgumentException;

abstract class Enum {
	
	private static $values = null;

	private $name;
	private $value;
	
	private function __construct($name, $value, $args = null) {
		$this->name = $name;
		$this->value = $value;
		
		$class = get_class($this);
//		if (null !== $args = property_exists($class, 'args') ? $class::$args[$value] : null) {
//			call_user_func_array(array($this, 'construct'), $args);
		if ($class::$args !== null) {
			call_user_func_array(array($this, 'construct'), $class::$args[$value]);
		} else {
			$this->construct();
		}
	}
	
//	protected function construct() {}
	
	public function value() {
		return $this->value;
	}
	
	public function name() {
		return $this->name;
	}
	
	public function __get($name) {
		switch ($name) {
			case 'name': return $this->name;
			case 'value': return $this->value;
		}
		throw new IllegalArgumentException();
	}
	
	public function __toString() {
		return "$this->value";
	}
	
	public function __invoke() {
		return $this->value();
	}
	
	private static function initStatic($class) {
		$rc = new \ReflectionClass($class);
		foreach ($rc->getConstants() as $k => $val) {
			self::$values[$k] = null;
		}
		if (method_exists($class, 'getArgs')) {
			$class::$args = $class::getArgs();
		} else {
			self::$args = null;
		}
	}
	
	public static function __callStatic($v, $args) {
		
		$class = get_called_class();
		
		if (self::$values === null) {
			self::initStatic($class);
		}
		
		if (array_key_exists($v, self::$values)) {
			if (self::$values[$v] === null) {
				self::$values[$v] = new $class($v, constant("$class::$v"));
			}
			return self::$values[$v];
		} else {
			throw new IllegalArgumentException();
		}
	}
	
}

// Example
//
//class MyEnum extends Enum {
//	
//	const BLA		= 2;
//	const BLABLA	= 3;
//	
//	static protected $args = array(
//		self::BLA => array('says'),
//		self::BLABLA => array('yells'),
//	);
//	
//	protected function construct($say) {
//		$this->say = $say;
//	}
//		
//	function hello() {
//		println("$this->name $this->say: hello!");
//	}
//}
//
//MyEnum::BLA()->hello();		// BLA says: hello!
//MyEnum::BLABLA()->hello();	// BLABLA yells: hello!
//
//$bla = MyEnum::BLA();		// get BLA instance
//println($bla . 2);		// (output: 22) $bla always convert to a string
//println($bla + 2);		// warn: cannot convert to int
//println($bla() + 2);		// (output: 4) $bla() forces the original value (instead of a string)
