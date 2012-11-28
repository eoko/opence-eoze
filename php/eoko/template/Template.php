<?php

namespace eoko\template;

require_once __DIR__ . '/Template.functions.php';

use eoko\log\Logger;
use IllegalArgumentException;

/**
 * @method \eoko\template\Template setFile(\string $filename)
 */
class Template extends Renderer {
	
	protected $vars = array();
	
	public function &__get($name) {
		return $this->vars[$name];
	}

	public final function __set($name, $value) {
		$this->set($name, $value);
	}
	
	public function __isset($name) {
		return isset($this->vars[$name]);
	}
	
	public function __unset($name) {
		unset($this->vars[$name]);
	}
	
	public function clearData() {
		$this->vars = array();
	}
	
	public function getData() {
		return $this->vars;
	}

	/**
	 * Set the Template's variable $name to the given $value.
	 * @param string $name
	 * @param mixed $value
	 * @return Template $this
	 */
	public function set($name, $value = null) {
		if (is_array($name)) {
			foreach ($name as $k => $v) {
				$this->set($k, $v);
			}
			return $this;
		}
		$this->vars[$name] = $value;
		return $this;
	}

	/**
	 * Add all the elements of the passed $in argument to this Template's vars.
	 *
	 * @param \Traversable $in
	 * @throws \IllegalArgumentException
	 * @return Template $this
	 */
	public function merge($in) {

		if (func_num_args() === 2) {
			return $this->mergeIn(func_get_arg(0), func_get_arg(1));
		}

		if (is_array($in))
			$this->vars = array_merge($this->vars, $in);
		else if (is_object($in)) {
			foreach ($in as $k => &$v) {
				$this->vars[$k] = $v;
			}
		} else {
			throw new IllegalArgumentException();
		}
		
		return $this;
	}

	public function mergeWithWarning($array, $loggerContext = null) {

		if ($loggerContext === null) $loggerContext = $this;

		foreach ($array as $k => &$v) {
			if (isset($this->vars[$k])) {
				Logger::get($loggerContext)->warn('Merge collision on {}', $k);
			}
			$this->vars[$k] = $v;
		}

		return $this;
	}

	/**
	 * @param string $name
	 * @param array $array
	 * @return Template $this
	 */
	public function mergeIn($name, array $array) {
		if (!isset($this->vars[$name])) {
			$this->vars[$name] = $array;
		} else {
			$this->vars[$name] = array_merge($this->vars[$name], $array);
		}
		return $this;
	}

	/**
	 * @param string $name
	 * @param mixed $val
	 * @return Template 
	 */
	public function push($name, $val) {
		if (!isset($this->vars[$name])) {
			$this->vars[$name] = array();
		}
		if (func_num_args() > 2) {
			foreach (array_slice(func_get_args(), 1) as $val) {
				array_push($this->vars[$name], $val);
			}
		} else {
			array_push($this->vars[$name], $val);
		}
		return $this;
	}

	/**
	 * @param string $name
	 * @param string $key
	 * @param mixed $value
	 * @return Template
	 */
	public function put($name, $key, $value) {
		if (!isset($this->vars[$name])) {
			$this->vars[$name] = array();
		}
		$this->vars[$name][$key] = $value;
		return $this;
	}

	protected function doRender() {
		extract($this->vars);
		eval("namespace eoko\\template { ?>{$this->getContent()}<? } ?>");
	}

}
