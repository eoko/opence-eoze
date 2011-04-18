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
		if (file_exists($path)) unlink($path);
		if (substr($filename, -4) !== '.php') $filename .= '.php';
		return file_put_contents($path, $code);
	}

	public static function getPhpFilePath($namespace, $filename) {
		$path = self::getNamespacePath($namespace) . DS . $filename;
		if (file_exists($path)) return $path;
		else return false;
	}

	public static function cacheData($class, $data, $version = null) {

		if (is_array($class)) {
			list($class, $key) = $class;
		}
		
		if ($version === null) {
			if (is_object($class) && isset($class->cacheVersion)) {
				$version = $class->cacheVersion;
			} else if (property_exists($class, 'cacheVersion')) {
				$version = $class::$cacheVersion;
			}
		}

//		$cache = new Item($data, $version);

		self::cachePhpFile(
			get_namespace($class), $class .
			'.DataCache' . (isset($key) ? ".$key" : '') . '.php',
			'<?php return ' . var_export($data, true) . ';'
		);
	}
	
	public static function getCachedData($class) {
		
		if (is_array($class)) {
			list($class, $key) = $class;
		}
		
		$file = self::getPhpFilePath(
			get_namespace($class), 
			$class . '.DataCache' . (isset($key) ? ".$key" : '') . '.php'
		);
		
		if (file_exists($file)) return require $file;
		else return null;
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
