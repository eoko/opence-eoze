<?php

namespace eoze\test\phpunit;

use IllegalStateException;
use IllegalArgumentException;
use eoko\util\Arrays;

use eoko\util\YmlReader;

/**
 * Array validator from a schema specification.
 * 
 * This implementation is mostly compatible with 
 * {@link http://www.kuwata-lab.com/kwalify/ruby/users-guide.01.html#schema Kwalify}'s
 * specifications.
 * 
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 24 nov. 2011
 */
class ArrayValidator {
	
	private $schema;
	
	private $sep = '.';
	
	private $errors;
	
	private $lastError;
	
	private $throwException;
	
	private $defaults = array(
		'strict'   => true,
		'required' => false,
		'allowNull' => true,
		'type' => 'str',
	);
	
	public function __construct($format, $throwException = false) {
		if (is_string($format)) {
			$format = YmlReader::load($format);
		}
		if (!is_array($format)) {
			throw new IllegalArgumentException(
				'$format must be an array'
			);
		}
		$this->throwException = $throwException;
		$this->schema = $format;
		if (isset($this->schema['defaults'])) {
			Arrays::apply($this->defaults, $this->schema['defaults']);
		}
	}
	
	private function testNull($spec) {
		foreach (array('', 'null', 'allowNull') as $opt) {
			if (isset($spec[$opt])) {
				return $spec[$opt];
			}
		}
		return !$this->isRequired($spec);
	}
	
	private function isAllowNull($spec, $parentSpec) {
		// The item itselff
		if ((null !== $r = $this->testNull($spec))
				|| isset($parentSpec['defaults']) && (null !== $r = $this->testNull($parentSpec['defaults']))
				|| (null !== $r = $this->testNull($this->defaults))) {
			return $r;
		}
		throw new IllegalStateException('Unreachable code');
	}
	
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
	
	private function getType($spec, $parentSpec) {
		if (array_key_exists('type', $spec)) {
			return $spec['type'];
		} else if (isset($parentSpec['defaults']['type'])) {
			return $parentSpec['defaults']['type'];
		} else if (isset($this->defaults['type'])) {
			return $this->defaults['type'];
		} else {
			return null;
		}
	}
	
	public function getLastError() {
		return $this->lastError;
	}
	
	public function test(array $array) {
		if (!isset($this->schema['type'])) {
			if (isset($this->schema['mapping'])) {
				$format = array(
					'type' => 'map',
				);
				Arrays::apply($format, $this->schema);
			} else if (isset($this->schema['sequence'])) {
				$format = array(
					'type' => 'seq',
				);
				Arrays::apply($format, $this->schema);
			} else {
				$format = array(
					'type' => 'map',
					'mapping' => $this->schema,
				);
			}
		} else {
			$format = $this->schema;
		}
		$this->errors = null;
		if (!self::testType('', $format, $array, $this)) {
			foreach ($this->errors as $path => $error) {
				if ($path) {
					if (substr($path, 0, 1) === $this->sep) {
						$path = substr($path, 1);
					}
					$this->lastError = "$path: $error";
					return false;
				} else {
					$this->lastError = $error;
					return false;
				}
			}
			return false;
		} else {
			return true;
		}
	}
	
	private function testMapping($path, array $spec, array $array) {
		$keys = $array;
		foreach ($spec['mapping'] as $key => $schema) {
			unset($keys[$key]);
			if (!is_array($schema)) {
				if ($schema === null) {
					$schema = array();
				} else {
					$schema = array(
						'value' => $schema,
						'required' => true,
					);
				}
			}
			if (!array_key_exists($key, $array)) {
				if ($this->isRequired($schema)) {
					$this->error("$path$this->sep$key", 'Missing required key');
					return false;
				}
			} else if (!$this->testRule("$path$this->sep$key", $schema, $array[$key], $spec)) {
				return false;
			}
		}
		if ($this->isStrict($spec) && count($keys)) {
			$key = key($keys);
			$this->error("$path$this->sep$key", "Undefined key");
			return false;
		}
		return true;
	}
	
	private function error($path, $msg) {
		if ($this->throwException) {
			throw new ValidationException($path, $msg);
		} else {
			$this->errors[$path] = $msg;
		}
		return false;
	}
	
	private static function exportValue($value) {
		if ($value === null) {
			return 'NULL';
		} else if (is_bool($value)) {
			return '(boolean) ' . ($value ? 'true' : 'false');
		} else {
			return $value;
		}
	}
	
	private function testRule($path, $spec, $value, $parentSpec) {
		if (array_key_exists('value', $spec)) {
			if ($spec['value'] === $value) {
				return true;
			} else {
				if ($spec['value'] == $value) {
					$this->error($path, 'Wrong type: expected map, found: ' .
							(is_array($value) ? 'seq' : gettype($value)));
				} else {
					$expected = self::exportValue($spec['value']);
					$value = self::exportValue($value);
					return $this->error($path, "Wrong required value: expected $expected, actual: $value");
				}
			}
		}
		if (isset($spec['enum'])) {
			if (in_array($value, $spec['enum'], true)) {
				return true;
			} else if (!in_array($value, $spec['enum']))  {
				// If the value is in the enum array but not with the expected
				// type, then we rely on the type option to decide if the test
				// passes or not.
				return $this->error($path, "Does not match enum: '$value')");
			}
		}
		if (!$this->testType($path, $spec, $value, $parentSpec)) {
			return false;
		}
		if (isset($spec['range'])) {
			if (isset($spec['type']) && in_array($spec['type'], array(
				'seq', 'sequence', 'map', 'bool'
			))) {
				throw new IllegalStateException('Range not applicable with type: ' . $spec['type']);
			}
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
		if (isset($spec['length'])) {
			if (isset($spec['type']) && !in_array($spec['type'], array(
				'str', 'text'
			))) {
				throw new IllegalStateException('Length not applicable with type: ' . $spec['type']);
			}
			$length = mb_strlen($value);
			if (isset($spec['length']['min']) && $length < $spec['length']['min']) {
				return $this->error($path, "Illegal length: $length < " . $spec['length']['min']);
				if (isset($spec['length']['min-ex'])) {
					throw new IllegalStateException('min and min-ex cannot be combined');
				}
			}
			if (isset($spec['length']['max']) && $length > $spec['length']['max']) {
				return $this->error($path, "Illegal length: $length > " . $spec['length']['max']);
				if (isset($spec['length']['max-ex'])) {
					throw new IllegalStateException('max and max-ex cannot be combined');
				}
			}
			if (isset($spec['length']['min-ex']) && $length <= $spec['length']['min-ex']) {
				return $this->error($path, "Illegal length: $length <= " . $spec['length']['min-ex']);
			}
			if (isset($spec['length']['max-ex']) && $length >= $spec['length']['max-ex']) {
				return $this->error($path, "Illegal length: $length >= " . $spec['length']['max-ex']);
			}
		}
		if (isset($spec['pattern'])
				&& !preg_match($spec['pattern'], $value)) {
			return $this->error($path, "Does not match pattern $spec[pattern]: '$value'");
		}
		return true;
	}
	
	private function testType($path, $spec, &$data, $parentSpec) {
		if ($data === null) {
			if (!$this->isAllowNull($spec, $parentSpec)) {
				$this->error($path, 'Forbidden NULL');
				return false;
			} else {
				return true;
			}
		}
		if ((null === $type = $this->getType($spec, $parentSpec))
				|| $type === 'any') { /* @uncovered: type=any */
			return true;
		}
		switch ($type) {
			case 'map':
				if (!Arrays::isAssocArray($data)) {
					return $this->error($path, 'Wrong type: expected map, found: ' .
							(is_array($data) ? 'seq' : gettype($data)));
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
					return $this->error($path, "Wrong type: expected $type, found: " . 
							(is_array(gettype($data)) ? 'map' : gettype($data)));
				} else if (isset($spec['sequence'])) {
					if (count($spec['sequence']) !== 1) {
						throw new IllegalStateException("Illegal specification for sequence: $path");
					}
					foreach ($data as $i => &$v) {
						if (!$this->testRule($path . "[$i]", $spec['sequence'][0], $v, $spec)) {
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
					$this->error($path, "Wrong type: expected $type, found: " . gettype($data));
					return false;
				} else {
					return true;
				}
			case 'int':
			case 'integer':
				if (!preg_match('/^-?\d+$/', $data)) {
					$this->error($path, "Wrong type: expected $type, found: " . gettype($data));
					return false;
				} else {
					$data = (int) $data;
					return true;
				}
			case 'bool':
			case 'boolean':
				if (!is_bool($data)) {
					$this->error($path, "Wrong type: expected $type, found: " . gettype($data));
					return false;
				} else {
					return true;
				}
			case 'float':
			case 'double':
				if (!is_float($data)) {
					$this->error($path, "Wrong type: expected $type, found: " . gettype($data));
					return false;
				} else {
					return true;
				}
			case 'number':
				if (is_integer($data) || is_float($data)) {
					return true;
				} else {
					$this->error($path, "Wrong type: expected $type, found: " . gettype($data));
					return false;
				}
			case 'text':
				if (is_string($data) || is_integer($data) || is_float($data)) {
					return true;
				} else {
					$this->error($path, "Wrong type: expected $type, found: " . gettype($data));
					return false;
				}
			case 'date':
				if (preg_match('/\d{4}-\d\d-\d\d/', $data)) {
					return true;
				} else {
					$this->error($path, "Not a date: " . $data);
					return false;
				}
			case 'scalar':
				if (!is_array($data)) {
					return true;
				} else {
					$this->error($path, "Wrong type: " . $data . ' (scalar expected)');
					return false;
				}
//			case 'mysql/boolean':
//			case 'mysql/bool':
//				if (in_array($data, array('0', '1'), true)) {
//					return true;
//				} else {
//					return $this->error(
//						$path, 
//						"Wrong type: expected $spec[type], found: " . gettype($data) . " = $data"
//					);
//				}
			default:
				if (gettype($data) !== $type) {
					$this->error($path, "Wrong type: expected $type, found: " . gettype($data));
					return false;
				} else {
					return true;
				}
		}
	}
}

class ValidationException extends \Exception {

	public function __construct($path, $error) {
		if ($path) {
			if (substr($path, 0, 1) === $this->sep) {
				$path = substr($path, 1);
			}
			$message = "$path: $error";
			return false;
		} else {
			$message = $error;
			return false;
		}
		parent::__construct($message);
	}
}
