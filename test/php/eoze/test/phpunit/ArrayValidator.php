<?php

namespace eoze\test\phpunit;

use IllegalStateException;
use eoko\util\Arrays;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 24 nov. 2011
 */
class ArrayValidator {
	
	private $format;
	
	private $sep = '.';
	
	public $errors;
	
	public function __construct(array $format) {
		$this->format = $format;
	}
	
	public function test(array $array) {
		if (!isset($this->format['type'])) {
			$format = array(
				'type' => 'map',
				'mapping' => $this->format,
			);
		} else {
			$format = $this->format;
		}
		$this->errors = null;
		if (!self::testType('', $format, $array)) {
			foreach ($this->errors as $path => $error) {
				if ($path) {
					if (substr($path, 0, 1) === $this->sep) {
						$path = substr($path, 1);
					}
					return "$path: $error";
				} else {
					return $error;
				}
			}
			return false;
		} else {
			return true;
		}
	}
	
	private $defaults = array(
		'strict' => false,
	);
	
	private function isStrict($spec) {
		if (!isset($spec['strict'])) {
			return $this->defaults['strict'];
		} else {
			return !!$spec['strict'];
		}
	}
	
	private function isRequired($spec) {
		return isset($spec['required']) && $spec['required'];
	}
	
	private function testMapping($path, array $spec, array $array) {
		$keys = $array;
		foreach ($spec['mapping'] as $key => $format) {
			unset($keys[$key]);
			if (!is_array($format)) {
				$format = array(
					'value' => $format
				);
//			} else if (!isset($spec['value']) && !isset($spec['type'])) {
//				throw new IllegalStateException("Illegal specification for item: $path$this->sep$name");
			}
			if (!array_key_exists($key, $array)) {
				if ($this->isRequired($format)) {
					$this->errors["$path$this->sep$key"] = 'Missing required key';
					return false;
				}
			} else {
				if (isset($format['type'])) {
					if (!$this->testType("$path$this->sep$key", $format, $array[$key])) {
						return false;
					}
				}
				if (isset($format['value'])) {
					if ($array[$key] !== $format['value']) {
						$this->errors["$path$this->sep$key"] = "Wrong required value: expected $format[value], actual: $array[name]";
						return false;
					}
				}
//				if (isset($spec['pattern'])) {
//
//				}
			}
		}
		if ($this->isStrict($spec) && count($keys)) {
			$key = key($keys);
			$this->errors["$path$this->sep$key"] = "Undefined key";
			return false;
		}
		return true;
	}
	
	private function testType($path, $spec, $data) {
		if ($data === null) {
			if (isset($spec['']) && !$spec['']) {
				$this->errors[$path] = 'Forbidden NULL';
				return false;
			} else {
				return true;
			}
		}
		switch ($spec['type']) {
			case 'map':
				if (!Arrays::isAssocArray($data)) {
					$this->errors[$path] = 'Wrong type: expected map, found: ' .
							(is_array($data) ? 'seq' : gettype($data));
					return false;
				} else {
					if (isset($spec['mapping'])) {
						return $this->testMapping($path, $spec, $data);
					} else {
						// free mapping
						return true;
					}
				}
			case 'seq':
			case 'sequence':
				if (!Arrays::isIndexedArray($data)) {
					$this->errors[$path] = "Wrong type: expected $spec[type], found: " . gettype($data);
					return false;
				} else if (isset($spec['sequence'])) {
					if (count($spec['sequence']) !== 1) {
						throw new IllegalStateException("Illegal specification for sequence: $path");
					}
					foreach ($data as $i => $v) {
						if (!$this->testType($path . "[$i]", $spec['sequence'][0], $v)) {
							return false;
						}
					}
					return true;
				} else {
					return true;
				}
			case 'str':
			case 'string':
				if (!is_string($data)) {
					$this->errors[$path] = "Wrong type: expected $spec[type], found: " . gettype($data);
					return false;
				} else {
					return true;
				}
			case 'int':
			case 'integer':
				if (!is_integer($data)) {
					$this->errors[$path] = "Wrong type: expected $spec[type], found: " . gettype($data);
					return false;
				} else {
					return true;
				}
			case 'bool':
			case 'boolean':
				if (!is_bool($data)) {
					$this->errors[$path] = "Wrong type: expected $spec[type], found: " . gettype($data);
					return false;
				} else {
					return true;
				}
			case 'float':
				if (!is_float($data)) {
					$this->errors[$path] = "Wrong type: expected $spec[type], found: " . gettype($data);
					return false;
				} else {
					return true;
				}
			case 'double':
				if (!is_double($data)) {
					$this->errors[$path] = "Wrong type: expected $spec[type], found: " . gettype($data);
					return false;
				} else {
					return true;
				}
			default:
				if (gettype($data) !== $spec['type']) {
					$this->errors[$path] = "Wrong type: expected $spec[type], found: " . gettype($data);
					return false;
				} else {
					return true;
				}
		}
	}
}
