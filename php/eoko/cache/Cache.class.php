<?php

namespace eoko\cache;

use eoko\log\Logger;

const PATH = CACHE_PATH;

/**
 * @todo real implementation
 */
class Cache {

	public static $mode = 0777;

	private function __construct() {}

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
		return file_put_contents($path, $code);
	}

	public static function getPhpFilePath($namespace, $filename) {
		$path = self::getNamespacePath($namespace) . DS . $filename;
		if (file_exists($path)) return $path;
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
	
	public static function cacheDataRaw($class, $code, $extraCode) {
		
		self::parseClassAndKey($class, $namespace, $key);
		
		$filenameBase = "$class.DataCache" . (isset($key) ? ".$key" : '');
		
//		$code = 'return ' . var_export($data, true) . ';';
		
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
		
		self::cachePhpFile(
			$namespace,
			"$filenameBase.php",
			"<?php $code"
		);
	}
	
	public static function cacheDataEx($class, $data, $extraCode) {
		self::cacheDataRaw($class, 'return ' . var_export($data, true) . ';', $extraCode);
	}
	
	public static function getCachedData($class) {
		
		self::parseClassAndKey($class, $namespace, $key);
		
		$file = self::getPhpFilePath(
			$namespace, 
			$class . '.DataCache' . (isset($key) ? ".$key" : '') . '.php'
		);
		
		if (file_exists($file)) return require $file;
		else return null;
	}
	
	public static function clearCachedData($class) {
		if (is_array($class)) {
			list($class, $key) = $class;
		}
		
		$file = self::getPhpFilePath(
			get_namespace($class),
			$class . '.DataCache' . (isset($key) ? ".$key" : '') . '.php'
		);
		
		if (file_exists($file)) {
			@unlink($file);
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
