<?php

namespace eoze\util;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 23 oct. 2011
 */
abstract class ObjectProxy {
	
	private $hook;
	
	public function __construct(&$hook) {
		$this->hook =& $hook;
		$hook = $this;
	}
	
	abstract protected function createObject();
	
	public function __call($name, $args) {
		$this->hook = $this->createObject();
		return call_user_func_array(array($this->hook, $name), $args);
	}
	
	public function __get($name) {
		$this->hook = $this->createObject();
		return $this->hook->$name;
	}
	
	public function __set($name, $value) {
		$this->hook = $this->createObject();
		$this->hook->$name = $value;
	}
}
