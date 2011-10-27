<?php

namespace eoze\util\Data;

use IllegalStateException;
use eoze\util\Data;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 27 oct. 2011
 */
class DataArray implements Data {
	
	private $values;
	
	public function __construct(array $values = array()) {
		$this->values = $values;
	}
	
	public function has($key) {
		return $this->node($key);
	}
	
	public function get($key) {
		if (null !== $v = $this->getOr($key, null)) {
			return $v;
		} else if ($this->has($key)) {
			return null;
		} else {
			throw new IllegalStateException('Undefined key: ' . $key);
		}
	}
	
	public function getOr($key, $default = null) {
		if ($this->node($key, $value)) {
			return $value;
		} else {
			return $default;
		}
	}
	
	private function node($key, &$value = null) {
		$parts = explode('.', $key);
		$node = $this->values;
		foreach ($parts as $k) {
			if (is_array($node) && array_key_exists($k, $node)) {
				$node = $node[$k];
			} else {
				return false;
			}
		}
		$value = $node;
		return true;
	}
}
