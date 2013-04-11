<?php

namespace eoko\cqlix;

use IllegalStateException;

class FieldMetadata {

	private $data;

	public function __construct($config) {
		$this->data = $config !== null ? $config : array();
	}

	public function get($name, $default = null) {
		return isset($this->data[$name]) ? $this->data[$name] : $default;
	}

	public function __get($name) {
		if (isset($this->data[$name])) {
			return $this->data[$name];
		} else {
			return null;
			throw new IllegalStateException('This field has no metadata for index: ' . $name);
		}
	}

	public function addCqlixFieldConfig(&$config) {
		foreach ($this->data as $k => $v) {
			if (isset($config[$k])) {
				// TODO: this should not be an error, meta should override default
				// column config, to allow for an easy mecanism to override
				// default config created by base column class (ie. ModelColumn)
				throw new IllegalStateException('Meta data conflicts with core data on key: ' . $k);
			}
			$config[$k] = $v;
		}
	}

	public function toArray() {
		return (array) $this->data;
	}
}
