<?php

use eoko\util\YmlReader as YAML;

class Config implements ArrayAccess, IteratorAggregate {

	protected $configName;
	protected $nodeName;
	protected $value;

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

		Logger::get($this)->warn('DEPRECATED: class \Config will be removed soon.'
				. ' You should use \eoko\config\Config');

//		dump_trace();

		$this->configName = $configName;
		$this->nodeName = $nodeName;
		$this->value = self::extractQualifiedArray($value); // replace aaa.xxx by aaa = array(xxx);
	}

	protected static function extractQualifiedArray($values) {
		// TODO rx: respect order !!!
		foreach ($values as $k => &$v) {
			if (count($parts = explode('.', $k)) > 1) {

				$node = array();
				$pnode =& $node;
				foreach ($parts as $part) {
					$pnode[$part] = array();
					$pnode =& $pnode[$part];
				}
				$pnode = $v;

				unset($values[$k]);

				$v = ArrayHelper::applyExtra($values, $node);
			}

			if (is_array($v)) $v = self::extractQualifiedArray($v);
		}
		return $values;
//		$r = array();
//		foreach ($values as $k => &$v) {
//			if (count($parts = explode('.', $k)) > 1) {
//
//				$node = array();
//				$pnode =& $node;
//				foreach ($parts as $part) {
//					$pnode[$part] = array();
//					$pnode =& $pnode[$part];
//				}
//				$pnode = $v;
//
//				unset($values[$k]);
//
//				$v = ArrayHelper::applyExtra($values, $node);
//				$v = array_shift($v);
//
//				$k = $parts[0];
//			}
//
//			if (is_array($v)) $v = self::extractQualifiedArray ($v);
//
//			$r[$k] = $v;
//		}
////		return $values;
//		return $r;
	}

	public static function createEmpty($nodeName = null, $configName = null) {
		$array = array();
		return new eoko\config\Config($array, $nodeName, $configName);
	}

	public function getNodeName() {
		return $this->nodeName;
	}

	public function getShortNodeName() {
		if ($$this->nodeName === null) throw new IllegalStateException();
		$parts = explode('/', $this->nodeName);
		return array_pop($parts);
	}

	public function getConfigName() {
		return $this->configName;
	}

	public function getConfigClass() {
		if ($this->configName === null) throw new IllegalStateException();
		$parts = explode('/', $this->configName);
		if (count($parts) < 2) throw new IllegalStateException();
		return $parts[0];
	}

	public static function find($rootNodeName, $dir = null) {
		if ($dir === null) $dir = substr(CONFIG_PATH,0,-1);
		else $dir = CONFIG_PATH . $dir;

		$files = FileHelper::listFiles($dir, 're:\.ya?ml$', true, true);
		foreach ($files as $file) {
			// TODO error here => Fatal error: Call to undefined method Config::create()
			$config = self::create(YAML::load(str_replace("\t", "  ", file_get_contents($file))));
			if ($config instanceof Config && $config->hasNode($rootNodeName)) {
				return $config->node($rootNodeName);
			}
		}

		return null;
	}

	/**
	 * Load a config node.
	 *
	 * @param string $name		name if the config item to load.
	 * @param string $node		name of the config node to load. If NULL, the
	 * root of the config item will be loaded in the returned object.
	 * @param boolean $require	if TRUE, an exception will be thrown if the
	 * given config item is not found or does not contain the required $node;
	 * if FALSE, this method will return NULL in these situations.
	 * @return Config
	 */
	public static function load($filename, $node = null, $require = true) {
		try {
			ob_start();
			include $filename;
			$content = ob_get_clean();
			return self::loadString(
				$content,
				$node,
				$require,
				$filename
			);
		} catch (Exception $ex) {
			throw new InvalidConfigurationException($filename, null, null, null, $ex);
		}
	}
	
	/**
	 * return \eoko\config\Config
	 */
	public static function loadString($contentString, $node = null, $require = true, $filename = null) {
		$values = YAML::load(str_replace("\t", "  ", $contentString));
		if (!is_array($values)) $values = array();
		return self::fromArray(
			$values,
			$node, $require, $filename
		);
	}

	/**
	 * return \eoko\config\Config
	 */
	public static function fromArray(array $values, $node = null, $require = true, $name = null) {

		$config = new \eoko\config\Config($values, null, $name);

		try {
			return $config->node($node, true);
		} catch (MissingConfigurationException $ex) {
			if ($require) throw $ex;
			else return null;
		}
	}

	const CREATE_STRING_FILE = 1;
	const CREATE_STRING_YML  = 2;
	const CREATE_REQUIRE     = 4;

	/**
	 * Creates a Config object from the given $config param, which can be either
	 * a string, an array of values, or an already instanciated
	 * Config object. In the later case, the same object will be returned
	 * unmodified. If the argument is a string, it will first be tested if a
	 * file with that name exists
	 * @param mixed $config
	 * @return \eoko\config\Config
	 */
	public static function create($config, $opts = 7) {
		if (is_string($config)) {
			if ($opts & self::CREATE_STRING_FILE && file_exists($config)) {
				return self::load($config);
			} else if ($opts & self::CREATE_STRING_YML) {
				return self::loadString($config);
			} else {
				throw new eoko\file\MissingFileException($config, FileType::YML);
			}
		} else if (is_array($config)) {
			return self::fromArray($config, null, $opts & self::CREATE_REQUIRE);
		} else if ($config instanceof Config) {
			return $config;
		} else {
			throw new IllegalArgumentException();
		}
	}
	
	/**
	 * Creates a Config object from the given $config param (see {@link
	 * Config::create()} for accepted inputs), and returns the node $node if
	 * it exists, or the root config if it doesn't.
	 * @param mixed $config see {@link Config::create()}
	 * @param string $node
	 * @param int $opts    see {@link Config::create()}
	 * @return Config
	 */
	public static function createForNode($config, $node, $opts = 7) {
		$r = self::create($config, $opts);
		if ($r->hasNode($node)) {
			return $r->node($node);
		} else {
			return $r;
		}
	}

	/**
	 * Gets whether this config contains a root node (or value) 
	 * with the given $name.
	 * 
	 * @param string $key
	 * @return bool
	 */
	public function has($key) {
		if (func_num_args() > 1) {
			$array =& $this->value;
			foreach (func_get_args() as $key) {
				if (!is_array($array) && !($array instanceof ArrayAccess)) {
					return false;
				}
				if (!array_key_exists($key, $array)) {
					return false;
				}
				$array =& $array[$key];
			}
			return true;
		} else {
			return array_key_exists($key, $this->value);
		}
	}

	/**
	 * Gets the config value for the given key, or $default if the option 
	 * is not set.
	 * 
	 * @param string $name   the config key to search
	 * @param mixed $default the default value to return if the option is not set
	 * @return mixed
	 */
	public function get($name, $default = null) {
		if ($this->has($name)) {
			return $this->value[$name];
		} else {
			return $default;
		}
	}
	
	/**
	 * Gets the value for the given path, or $default if the given config 
	 * option is not set.
	 * 
	 * @param string $path      The slash-separated (/) path.
	 * @param mixed  $default   The default value to return if the option is not set.
	 * @param bool   $rawArrays TRUE to return raw arrays, FALSE to return Config objects.
	 * 
	 * @return mixed
	 */
	public function getValue($path, $default = null, $rawArrays = false) {
		if ($this->hasNode($path)) {
			$node = $this->node($path, false, false);
			if ($rawArrays && $node instanceof Config) {
				return $node->toArray();
			} else {
				return $node;
			}
		} else {
			return $default;
		}
	}

	/**
	 * @param <type> $name
	 * @return <type>
	 *
	 * @throws IllegalArgumentException if $name doesn't match an actual,
	 * already set key in the config value array.
	 *
	 * Only Config values that actually exists can be accessed with the ->
	 * operator. Keys existence should be tested with isset()
	 * (eg. <code>isset($config['myKey'])</code>) before trying to access them.
	 */
	public function &__get($name) {

		if (array_key_exists($name, $this->value)) {
			return $this->value[$name];
		} else {
			$ex = new IllegalArgumentException('Invalid config key (not set): ' . $name);
			$ex->addDocRef(get_class() . '::' . '__get()');
			throw $ex;
		}
	}

	public function __set($name, $value) {
		$this->value[$name] = $value;
	}

	public function offsetExists($offset) {
		return array_key_exists($offset, $this->value);
	}

	public function offsetGet($offset) {
		return $this->value[$offset];
	}

	public function offsetSet($offset, $value) {
		$this->value[$offset] = $value;
	}

	public function offsetUnset($offset) {
		unset($this->value[$offset]);
	}

	public function getIterator() {
		return new ArrayIterator($this->value);
		// return @eval('return new ArrayIterator(&$this->value);');
	}

	/**
	 * Get whether this config contains a node specified by its fully qualified
	 * name (eg 'root/node1/childnode')
	 * @param string $path
	 * @return bool
	 */
	public function hasNode($path) {
		if (is_string($path)) {
			$path = explode('/', $path);
		}

		if (!is_array($path)) {
			return isset($this->value[$path]);
		} else {
			$node = $this->value;
			for ($i=0,$l=count($path); $i<$l; $i++) {
				if (!isset($node[$path[$i]])) return false;
				else $node = $node[$path[$i]];
			}
			return true;
		}
	}

	/**
	 * Retrieve a node by its fully qualified name (eg. 'rootnode/name/child').
	 * @param array $path	the fully qualified name of the node to retrieve.
	 * @param $require		if TRUE, an exception will be thrown if the specified
	 * node cannot be found; if FALSE, an empty Config object will be returned
	 * in this situation (this object will be considered to represent the specified
	 * node -- ie. its $nodeName and $configName will be set accordingly).
	 * @return Config
	 * @throws MissingConfigurationException if the config doesn't contain a
	 * node with the specified name
	 */
	public function node($path, $require = false, $createIfNeeded = true) {
		
		if ($path === null) {
			return $this;
		} else if (is_string($path)) {
			$pathElt = explode('/', $path);
		}
		
		if (count($pathElt) == 1) {
			if (isset($this->value[$path])) {
				if (is_array($this->value[$path])) {
					return new eoko\config\Config($this->value[$path], $path, $this->configName);
				} else {
					return $this->value[$path];
				}
			} else if ($require) {
				MissingConfigurationException::throwFrom($this,
						"Cannot resolve path: $path");
			} else if ($createIfNeeded) {
				return Config::createEmpty($path, $this->configName);
			} else {
				return null;
			}
		}

		// Resolve actual path
		$node = $this->value;
		$resolving = array();
		for ($i=0,$l=count($pathElt); $i<$l; $i++) {
			$resolving[] = $pathElt[$i];
			if (!isset($node[$pathElt[$i]])) {
				if ($require) {
					MissingConfigurationException::throwFrom($this,
							sprintf('Cannot resolve path: %s (missing node: %s)',
									$path, implode('/', $resolving)));
				} else if ($createIfNeeded) {
					return \eoko\Config\Config::createEmpty($path, $this->configName);
				} else {
					return null;
				}
			} else {
				$node = $node[$pathElt[$i]];
			}
		}

		if (is_array($node) || $node instanceof ArrayAccess) {
			return new \eoko\Config\Config($node, $path, $this->configName);
		} else {
			return $node;
		}
	}
	
	/**
	 * Apply the given $values to this config (ie. set the keys in this config
	 * to their value in the array).
	 * @param array $values
	 * @return Config this config item
	 * @internal default for $maxRecursionLevel changed on 01/03/11 07:30
	 */
//	public function apply($values, $maxRecursionLevel = true) {
	public function apply($values, $maxRecursionLevel = false) {
		ArrayHelper::apply($this->value, $values, $maxRecursionLevel);
		return $this;
	}

	/**
	 * Apply the given $values to this config, if they are not already set
	 * (ie. set the keys in this config to their value in the array).
	 * @param array $values
	 * @return Config this config item
	 */
	public function applyIf($vals, $maxRecursionLevel = true, $applyNumericalIndexArray = false) {
		ArrayHelper::applyIf($this->value, $vals, $maxRecursionLevel, $applyNumericalIndexArray);
		return $this;
	}

	public function toArray($recursive = true) {
		$r = array();
		foreach ($this->value as $k => $v) {
			if ($recursive && $v instanceof Config) {
				$r[$k] = $v->toArray(true);
			} else {
				$r[$k] = $v;
			}
		}
		return $r;
	}

	public function prepend($key, $value) {
		$this->value = array_merge(array($key => $value), $this->value);
	}
}
