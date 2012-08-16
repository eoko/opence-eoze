<?php

namespace eoko\util;

class Proxy implements HasProxy {
	
	private $attachedVars = null;
	private $instance = null;
	
	private $class;
	private $constructArgs;
	
	public function __construct($class, $constructArgs) {
		$this->class = $class;
		$this->constructArgs = $constructArgs;
	}
	
	public function attach(&$var) {
		if ($this->instance) $var = $instance;
		return $this->attachedVars[] = $var;
	}
	
	private function instanciate() {
		if ($this->instance) return $this->instance;
		$this->instance = $this->createInstance();
		foreach ($this->attachedVars as &$var) {
			$var = $this->instance;
		}
		$this->attachedVars = null;
		return $instance;
	}
	
	protected function createInstance() {
		$class = $this->class;
		if (!$this->constructArgs) {
			return new $class;
		} else if (count($this->constructArgs) == 1) {
			return new $class($this->constructArgs[0]);
		} else if (count($this->constructArgs) == 2) {
			return new $class($this->constructArgs[0], $this->constructArgs[1]);
		} else if (count($this->constructArgs) == 3) {
			return new $class($this->constructArgs[0], $this->constructArgs[1], $this->constructArgs[2]);
		} else {
			$ro = new \ReflectionObject($class);
			return $ro->newInstanceArgs($this->constructArgs);
		}
	}
	
	public function __call($name, $args) {
		call_user_func_array(array($this->instanciate(), $name), $args);
	}
	
	public function __get($name) {
		return $this->instanciate()->$name;
	}
	
	public function __set($name, $value) {
		$this->instanciat()->name = $value;
	}
}