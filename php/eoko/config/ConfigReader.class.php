<?php

namespace eoko\config;

use eoko\util\Arrays;

use \IllegalStateException;

class ConfigReader {

	private static $readers = array();

	protected $data = array();

	private final function __construct() {
		$this->construct();
	}

	protected function construct() {}
	
	protected function process(array &$content) {
		return Arrays::applyIf($this->data, $content, false);
	}

	public static function read($node, &$content) {
		return self::getConfigReader($node)->process($content);
	}

	/**
	 * @param string $node
	 * @return ConfigReader
	 */
	private static function getConfigReader($node) {
		if (!isset(self::$readers[$node])) {
			self::$readers[$node] = self::createReader($node);
		}
		return self::$readers[$node];
	}

	/**
	 * @param string $node
	 * @return ConfigReader
	 */
	private static function createReader($node) {
		$ns = ltrim(str_replace('/', '\\', $node), '\\');
		if (class_exists($ns)) {
			$ns = preg_replace('/\\\\[^\\\\]+$/', '', $ns);
		}
		if (class_exists($class = "$ns\\ConfigReader")) {
			if (!is_subclass_of($class, __CLASS__) && $class !== __CLASS__) {
				throw new IllegalStateException("Config reader $class must extend " . __CLASS__);
			}
            
            $o = new $class;
            
            // PHP namespaces are case-insensitive, while eoze config node namespaces
            // are case-sensitive, so we must ensure that the class name of the ConfigReader
            // that has been found is *really* the one expected
            if (get_class($o) === $class) {
                return $o;
            }
		}
        
        return new ConfigReader();
	}

}
