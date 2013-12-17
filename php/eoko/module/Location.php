<?php

namespace eoko\module;

abstract class Location {

	public $path;
	public $url;
	public $namespace;

	private function __construct() {}

	protected function construct($path, $url, $namespace) {
		$this->path = $path;
		$this->url = $url;
		$this->namespace = $namespace;
	}

	protected static function createInstance() {
		$class = get_called_class();
		return new $class;
	}

	public function __toString() {
		return "Path: $this->path; URL: $this->url; Namespace: $this->namespace";
	}

	protected function setPrivateState(&$vals) {}

	public static function __set_state($vals) {
		$o = self::createInstance();
		$o->setPrivateState($vals);
		foreach ($vals as $k => $v) {
			$o->$k = $v;
		}
		return $o;
	}
}
