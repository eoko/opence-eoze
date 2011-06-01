<?php

namespace eoko\cache;

use eoko\log\Logger;

const PATH = CACHE_PATH;

/**
 * @todo real implementation
 */
class Cache {

	public static $mode = 0777;
	public static $debugInfo = false;
	
	private static $useValidityMonitors = true;

	private function __construct() {}
	
	public static function getClassName() {
		return get_called_class();
	}

	public static function cachePhpClass($class, $code) {
		return self::cachePhpFile(
			parseNamespace($class, $class, GET_NAMESPACE_TRIM),
			"$class.class.php",
			$code
		);
	}

	private static function getNamespacePath($ns) {
		return PATH . 'php' . DS . str_replace('\\', DS, trim($ns, '\\'));
	}

	private static function createNamespaceDir($ns) {
		$nsPath = self::getNamespacePath($ns);
		if (!file_exists($nsPath)) mkdir($nsPath, self::$mode, true);
		return $nsPath . DS;
	}

	public static function cachePhpFile($namespace, $filename, $code) {
		$nsPath = self::createNamespaceDir($namespace);
		if (!is_writeable($nsPath)) {
			Logger::get(get_called_class())->warn('Cannot write cache: {}', $nsPath);
			return false;
		}
		$path = "$nsPath$filename";
		if (file_exists($path)) {
			@unlink($path);
		}
		if (substr($filename, -4) !== '.php') $filename .= '.php';
		file_put_contents($path, $code);
		return $path;
	}

	public static function getPhpFilePath($namespace, $filename, $onlyIfExists = true) {
		$path = self::getNamespacePath($namespace) . DS . $filename;
		if ($onlyIfExists && file_exists($path)) return $path;
		else return false;
	}
	
	private static function parseClassAndKey(&$class, &$namespace, &$key) {
		if (is_array($class)) {
			list($class, $key) = $class;
		}
		if (is_object($class)) {
			$class = get_class($class);
			$namespace = get_namespace($class);
		}
		if (preg_match('/^(.+)\\\\([^\\\\]+)$/', $class, $m)) {
			$class = $m[2];
			$namespace = $m[1];
		} else {
			$namespace = null;
		}
	}

	/**
	 * Removes any reference to an instanciated object in the given cache key,
	 * replacing then with the full class name string. This is useful to avoid
	 * storing uselessly whole object, when storing a cache key.
	 * @param object|class|string $key
	 * @return mixed
	 */
	public static function flattenKey(&$key) {
		if ($key) {
			if (is_array($key)) {
				$key[0] = self::flattenKey($key[0]);
			} else if (is_object($key)) {
				$key = get_class($key);
			}
		}
		return $key;
	}
	
	private static function appendKey(&$class, $key) {
		if (is_array($class)) {
			$class = array(
				$class[0],
				$class[1] . ".$key",
			);
		} else {
			return array(
				$class, $key
			);
		}
	}

	public static function cacheData($class, $data, $version = null, $requires = null) {
		
		self::parseClassAndKey($class, $namespace, $key);

		if ($version === null) {
			if (is_object($class) && isset($class->cacheVersion)) {
				$version = $class->cacheVersion;
			} else if (property_exists($class, 'cacheVersion')) {
				$version = $class::$cacheVersion;
			}
		}

//		$cache = new Item($data, $version);

		self::cachePhpFile(
			$namespace, $class .
			'.DataCache' . (isset($key) ? ".$key" : '') . '.php',
			'<?php return ' . var_export($data, true) . ';'
		);
	}
	
	public static function cacheObject($class, $object, $extraCode = null) {
		
		$debug = '';
		
		if (Logger::get(get_called_class())->isActive(Logger::DEBUG)) {
			$debug = print_r($object, true);
			$debug = "\n\n/* === Debug Informations ===\n\n$debug\n*/";
		}
		
		return self::cacheDataRaw(
			$class,
			'return unserialize(\'' 
				. str_replace('\'', '\\\'', serialize($object))
				. '\');'
				. $debug,
			$extraCode
		);
	}
	
	public static function cacheDataRaw($class, $code, $extraCode = null) {
		
		self::parseClassAndKey($class, $namespace, $key);
		
		$filenameBase = "$class.DataCache" . (isset($key) ? ".$key" : '');
		
		if ($extraCode) {
			$depFileName = "$filenameBase.inc.php";
			// cache dep file
			if (is_array($extraCode)) {
				$tmp = '';
				foreach ($extraCode as $xc) {
					$tmp .= PHP_EOL . $xc;
				}
				$extraCode = $tmp;
			}
			self::cachePhpFile(
				$namespace, 
				$depFileName, 
				"<?php $extraCode"
			);

			// add the require to the base code
			$code = PHP_EOL . "require dirname(__FILE__) . DS . '$depFileName';" 
				. PHP_EOL . $code;
		}
		
		return self::cachePhpFile(
			$namespace,
			"$filenameBase.php",
			"<?php $code"
		);
	}
	
	public static function cacheDataEx($class, $data, $extraCode) {
		self::cacheDataRaw($class, 'return ' . var_export($data, true) . ';', $extraCode);
	}
	
	/**
	 * Set the validity of the cache node specified by $class dependant on
	 * modifications of the files in $files.
	 * @param mixed $class
	 * @param array[string] $files 
	 */
	public static function monitorFiles($class, $files) {
		if (self::$useValidityMonitors) {
			self::appendKey($class, 'validity');
			$validator = new FileValidator($files);
			self::cacheObject($class, $validator);
		}
	}
	
	public static function getCachedData($class) {
		
		self::parseClassAndKey($class, $namespace, $key);
		$baseFilename = $class . '.DataCache' . (isset($key) ? ".$key" : '');
		
		if (self::$useValidityMonitors) {
			$file = self::getPhpFilePath(
				$namespace, 
				"$baseFilename.validity.php"
			);
			if ($file) {
				$validator = require $file;
//				dump($validator);
				if (!$validator->test()) {
					Logger::get(get_called_class())->debug(
						'Cache invalidated for: {}.{}', $class, $key
					);
					self::clearCachedData($class);
					return null;
				}
			}
		}
		
		$file = self::getPhpFilePath(
			$namespace, 
			$baseFilename . '.php'
		);
		
		if ($file) return require $file;
		else return null;
	}
	
	public static function clearCachedData($class) {
		if (is_array($class)) {
			list($class, $key) = $class;
		}
		
		$base = $class . '.DataCache' . (isset($key) ? ".$key" : '');
		
		foreach (array(
			'',
			'.inc',
			'.validity',
		) as $ext) {
			$file = self::getPhpFilePath(
				get_namespace($class),
				"$base$ext.php"
			);
			if ($file) {
				@unlink($file);
			}
		}
	}

	private static function makeObjectCacheFilename($class, $version, $index) {
		return "{$class}__{$version}__{$index}.cache.php";
	}

	private function parseObjectInfo($filename, &$version = null, &$index = null) {
		$parts = explode('__', $filename);
		if (count($parts) === 3) {
			list(, $version, $index) = $parts;
			if ($index === '') $index = null;
		} else {
			throw new IllegalStateException('Invalid cache filename: ' . $filename);
		}
	}

	public static function cacheSingleton($object, $version) {
		return self::cachePhpFile(
			get_namespace(get_class($object), $class),
			self::makeObjectCacheFilename($class, $version, null),
			'<?php ' . var_export($object, true) . ';'
		);
	}

	public static function getCachedSingleton($class, $version) {
		$nsPath = self::getNamespacePath(get_namespace($class, $class)) . DS;
		$filename = self::makeObjectCacheFilename($class, $version, null);
		if (file_exists($path = $nsPath . $filename)) return require($path);
		else return null;
	}
}

Cache::$debugInfo = Logger::get(Cache::getClassName())->isActive(Logger::DEBUG);