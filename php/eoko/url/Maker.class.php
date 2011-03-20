<?php

namespace eoko\url;

use eoko\util\Arrays;
use eoko\config\ConfigManager;

const BASE_URL = \SITE_BASE_URL;

class Maker {

	private function __construct() {}
	
	private static $extra = null;
	
	private static $config = null;
	
	private static function getConfig() {
		if (!self::$config) {
			self::$config = ConfigManager::get(__NAMESPACE__ . '\routes');
		}
		return self::$config;
	}
	
	private static function makeControllerAction($controller, $action, &$base) {
		
		$config = self::getConfig();
		
		if (isset($config['static'])) {
			foreach ($config['static'] as $alias => $cfg) {
				if ((is_array($cfg['controller']) ? false !== array_search($controller, $cfg['controller']) 
						: $cfg['controller'] === $controller)
						&& $cfg['action'] === $action) {
					
					$base = $alias;
					return array();
				}
			} 
		}

		$base = 'index.php';
		
		$parts = array();
		
		if ($controller !== null) {
			$parts[] = "controller=$controller";
		}
		
		if ($action !== null) $parts[] = "action=$action";
		
		return $parts;
	}
	
	public static function getFor($controller, $action = null, $params = null, $anchor = null) {
		
		if ($controller instanceof \eoko\module\executor\Executor) {
			$controller = "$controller->module.$controller";
		}
		
		if (is_array($controller)) {
			extract($controller);
		}
		
//		$parts = array();
		$parts = self::makeControllerAction($controller, $action, $base);

		if ($params !== null) {
			foreach ($params as $k => $v) $parts[] = "$k=$v";
		}
		
		$base .= '?' . implode('&', $parts);
		
//		$parts = implode('&', $parts);
		
		if ($anchor !== null) {
			$base .= "#$anchor";
//			$anchor = "#$anchor";
		}
		
		return self::makeAbsolute(
//			urlencode(
				"$base$anchor"
//			)
		);
	}
	
	public static function makeAbsolute($url) {
		if (substr($url, 0, 7) === 'http://') return $url;
		else return BASE_URL . $url;
	}
	
	public static function getExtra() {
		$extra = self::$extra;
		self::$extra = null;
		return $extra;
	}
}