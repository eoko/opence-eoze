<?php

namespace eoko\context;

use Exception;

class Context {

	private $parent = null;

	public final function __construct(Context $parent = null) {
		$this->parent = $parent;
		if (method_exists($this, 'construct')) {
			$args = func_get_args();
			array_shift($args);
			$this->call($method, $args);
		}
	}

	private function call($method, $args = null) {
		if (!$args) {
			return $this->$method();
		}

		$n = count($args);

		if ($n === 1) {
			return $this->$method($args[0]);
		} else if ($n === 2) {
			return $this->$method($args[0], $args[1]);
		} else if ($n === 3) {
			return $this->$method($args[0], $args[1], $args[2]);
		} else if ($n === 4) {
			return $this->$method($args[0], $args[1], $args[2], $args[3]);
		} else {
			return call_user_func_array(array($this, $method), $args);
		}
	}

	public function isCli() {
		return php_sapi_name() !== 'cli';
	}

	public function isRequest() {
		return !$this->isCli() && ($_SERVER ? true : false);
	}

	public function __get($name) {
		if ($parent) {
			return $this->parent->$name;
		} else {
			throw new Exception(get_class($this) . ' has no property ' . $name);
		}
	}

	public function __call($name, $args) {
		if ($parent) {
			return $this->parent->call($name, $args);
		} else {
			throw new Exception(get_class($this) . ' has no method ' . $name);
		}
	}
}

class PartialContext {
	public function __construct($values = null) {
		if ($values) {
			foreach ($values as $k => $v) {
				$this->$k = $v;
			}
		}
	}
}

class SourcePath extends PartialContext {
	public $namespace;
	public $path;
}

class Eoze extends SourcePath {

}

class ContextBase extends Context {

	public $rootPath;
	public $baseUrl;

	/** @var Eoze */
	public $eoze;

}
