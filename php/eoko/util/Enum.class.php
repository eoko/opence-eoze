<?php

namespace eoko\util;

use IllegalArgumentException;

abstract class Enum {
	
	private static $values = null;

	private $name;
	private $value;
	
	static protected $args = array();
	
	private function __construct($name, $value, $args = null) {
		$this->name = $name;
		$this->value = $value;
		
		$class = get_class($this);
		if (isset(self::$args[$class][$value])) {
			call_user_func_array(array($this, 'construct'), self::$args[$class][$value]);
		} else {
			$this->construct();
		}
	}
	
	protected function construct() {}
	
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
			self::$values[$class][$k] = null;
		}
		if (method_exists($class, 'getArgs')) {
			self::$args[$class] = $class::getArgs();
		} else {
			self::$args = null;
		}
	}
	
	public static function __callStatic($v, $args) {
		
		$class = get_called_class();
		
		if (!isset(self::$values[$class])) {
			self::initStatic($class);
		}
		
		$values = self::$values[$class];
		
		if (array_key_exists($v, $values)) {
			if ($values[$v] === null) {
				$values[$v] = new $class($v, constant("$class::$v"));
			}
			return $values[$v];
		} else {
			throw new IllegalArgumentException("Undefined enum value: $v not in ["
					. implode(', ', array_keys($values)) . ']');
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
