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
		'strict'   => true,
		'required' => false,
	);
	
	private function isStrict($spec) {
		if (!isset($spec['strict'])) {
			return $this->defaults['strict'];
		} else {
			return !!$spec['strict'];
		}
	}
	
	private function isRequired($spec) {
		if (!isset($spec['required'])) {
			return $this->defaults['required'];
		} else {
			return !!$spec['required'];
		}
	}
	
	private function testMapping($path, array $spec, array &$array) {
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
			} else if (!$this->testRule("$path$this->sep$key", $format, $array[$key])) {
				return false;
			}
		}
		if ($this->isStrict($spec) && count($keys)) {
			$key = key($keys);
			$this->errors["$path$this->sep$key"] = "Undefined key";
			return false;
		}
		return true;
	}
	
	private function error($path, $msg) {
		$this->errors[$path] = $msg;
		return false;
	}
	
	private function testRule($path, $spec, &$value) {
		if (isset($spec['type'])
				&& !$this->testType($path, $spec, $value)) {
			return false;
		}
		if (isset($spec['value']) && $value !== $spec['value']) {
			return $this->error($path, "Wrong required value: expected $spec[value], actual: $value");
		}
		if (isset($spec['range'])) {
			if (isset($spec['range']['min']) && $value < $spec['range']['min']) {
				return $this->error($path, "Out of range: $value < " . $spec['range']['min']);
				if (isset($spec['range']['min-ex'])) {
					throw new IllegalStateException('min and min-ex cannot be combined');
				}
			}
			if (isset($spec['range']['max']) && $value > $spec['range']['max']) {
				return $this->error($path, "Out of range: $value > " . $spec['range']['max']);
				if (isset($spec['range']['max-ex'])) {
					throw new IllegalStateException('max and max-ex cannot be combined');
				}
			}
			if (isset($spec['range']['min-ex']) && $value <= $spec['range']['min-ex']) {
				return $this->error($path, "Out of range: $value <= " . $spec['range']['min-ex']);
			}
			if (isset($spec['range']['max-ex']) && $value >= $spec['range']['max-ex']) {
				return $this->error($path, "Out of range: $value >= " . $spec['range']['max-ex']);
			}
		}
		if (isset($spec['pattern'])
				&& !preg_match($spec['pattern'], $value)) {
			return $this->error($path, "Does not match pattern $spec[pattern]: '$value'");
		}
		if (isset($spec['enum'])
				&& !in_array($value, $spec['enum'], true)) {
			return $this->error($path, "Does not match enum: '$value')");
		}
		return true;
	}
	
	private function testType($path, $spec, &$data) {
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
					foreach ($data as $i => &$v) {
						if (!$this->testRule($path . "[$i]", $spec['sequence'][0], $v)) {
							return false;
						}
					}
					unset($v);
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
				if (!preg_match('/^-?\d+$/', $data)) {
					$this->errors[$path] = "Wrong type: expected $spec[type], found: " . gettype($data);
					return false;
				} else {
					$data = (int) $data;
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
			case 'double':
				if (!is_float($data)) {
					$this->errors[$path] = "Wrong type: expected $spec[type], found: " . gettype($data);
					return false;
				} else {
					return true;
				}
			case 'number':
				if (is_integer($data) || is_float($data)) {
					return true;
				} else {
					$this->errors[$path] = "Wrong type: expected $spec[type], found: " . gettype($data);
					return false;
				}
			case 'text':
				if (is_string($data) || is_integer($data) || is_float($data)) {
					return true;
				} else {
					$this->errors[$path] = "Wrong type: expected $spec[type], found: " . gettype($data);
					return false;
				}
			case 'date':
				if (preg_match('/\d{4}-\d\d-\d\d/', $data)) {
					return true;
				} else {
					$this->errors[$path] = "Not a date: " . $data;
					return false;
				}
			case 'scalar':
				if (!is_array($data)) {
					return true;
				} else {
					$this->errors[$path] = "Wrong type: " . $data . ' (scalar expected)';
					return false;
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
