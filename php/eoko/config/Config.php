<?php

namespace eoko\config;

use IllegalStateException;

class Config extends \Config {

	/**
	 * Create a new Config object.
	 * @param mixed $value			array or another Config object containing
	 * the data to initialize this config object. If $value is NULL, an empty
	 * config is created.
	 * @param string $nodeName		name of the config node represented by this
	 * object.
	 * @param string $configName	name of the config item (which generally
	 * refer to the filename) represented by this object.
	 */
	public function __construct(&$value = array(), $nodeName = null, $configName = null) {
		$this->configName = $configName;
		$this->nodeName = $nodeName;
		$this->value = self::extractQualifiedArray($value); // replace aaa.xxx by aaa = array(xxx);
	}

	public function &__get($name) {
		if (array_key_exists($name, $this->value)) {
			return $this->value[$name];
		} else {
			$ex = new InvalidConfigKey('Undefined config key: ' . $name);
			$ex->addDocRef(get_class() . '::' . '__get()');
			throw $ex;
		}
	}

	public function offsetGet($offset) {
		if (array_key_exists($offset, $this->value)) {
			return $this->value[$offset];
		} else {
			$ex = new InvalidConfigKey('Undefined config key: ' . $offset);
			$ex->addDocRef(get_class() . '::' . '__get()');
			throw $ex;
		}
	}

}

class InvalidConfigKey extends IllegalStateException {}