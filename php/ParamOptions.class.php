<?php

class ParamOptions {

	// we don't want people to create ParamOption, it is either intended to
	// be used for its static methods, or it is instanciated width the create()
	// method -- which create a ParamOption (without a s!) class, to avoid
	// confustion with the ParamOption's static methods
	private function __construct() {
	}

	/**
	 *
	 * @param array $values
	 * @return ParamOption 
	 */
	public static function create(array $values = null) {
		return new ParamOption($values);
	}

	/**
	 *
	 * @param array|ParamOption $opts
	 * @param string $paramName
	 * @param mixed $defaultValue returned if !isset($opts) || $opts === NULL
	 * @return mixed
	 */
	public static function get($opts, $paramName, $defaultValue = null) {
		if (is_array($paramName)) {
			foreach ($paramName as $pm) {
				if (null !== $r = ParamOption::get($opts, $paramName, $defaultValue)) {
					return $r;
				}
			}
			return $defaultValue;
		}
		if ($opts !== null && isset($opts[$paramName])) {
			return $opts[$paramName];
		} else {
			return $defaultValue;
		}
	}

	/**
	 * Set the given param to the given value, in the given array or options
	 * object. Multiple pair param => value can be given as an array in the 2nd
	 * param ($param).
	 * @param ParamOptions|array $opts
	 * @param string|array $param
	 * @param mixed $value
	 */
	public static function applyIf(&$opts, $param, $value = null) {
		if (is_array($param)) {
			if ($value !== null) {
				Logger::get($this)->error(
					'$param must be an array $paramName => $value if the $value param is NULL'
				);
			} else {
				foreach ($param as $name => $value) {
					$opts = $this->applyIf($opts, $name, $value);
				}
			}
		} else {
			if ($opts !== null && !isset($opts[$param])) $opts[$param] = $value;
		}
		return $opts;
	}
}

class ParamOption implements ArrayAccess {

	private $values = null;

	public function __construct(array $values = null) {
		$this->values = $values;
	}

	public function offsetExists($offset) {
		isset($this->values[$offset]);
	}

	public function offsetGet($offset) {
		if (!isset($this->values[$offset])) return null;
		else return $this->values[$offset];
	}

	public function offsetSet($offset, $value) {
		$this->values[$offset] = $value;
	}

	public function offsetUnset($offset) {
		unset($this->values[$offset]);
	}

}