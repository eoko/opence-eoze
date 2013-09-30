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
		if (self::$config === null) {
			self::$config = self::loadConfig();
		}
		return self::$config;
	}

	private static function loadConfig() {
		return ConfigManager::get(__NAMESPACE__ . '\\routes');
	}

	public static function populateRouteRequest(array &$request, $route = null) {

		if ($route === null) $route = $request['route'];

		$config = self::getConfig();
		if (!isset($config['static']) || !isset($config['static'][$route])) {
			throw new \IllegalStateException('Route does not exist: ' . $route);
		}
		$route = $config['static'][$route];
		if (isset($route['controller'])) {
			$request['controller'] = is_array($route['controller']) ?
					array_shift($route['controller']) : $route['controller'];
		}
		if (isset($route['action'])) {
			$request['action'] = is_array($route['action']) ?
					array_shift($route['action']) : $route['action'];
		}
		if (isset($route['params'])) {
			foreach ($route['params'] as $k => $v) $request[$k] = $v;
		}
	}

	private static function matchRoutePart($route, $part, $value) {
		if (!isset($route[$part])) {
			if ($value === null) return true;
			else return false;
		} else if (is_array($route[$part])) {
			return array_search($value, $route[$part]) !== false;
		} else {
			return $route[$part] == $value;
		}
	}

	private static function getRouteAlias($controller, $action, $params) {
		$config = self::getConfig();
		if (!isset($config['static'])) return null;

		foreach ($config['static'] as $alias => $route) {
			if (self::matchRoutePart($route, 'controller', $controller)
					&& self::matchRoutePart($route, 'action', $action)) {

				if (!$params) {
					if (!isset($route['params']) || !$route['params']) return $alias;
				} else {
					if (!isset($route['params']) || !$route['params']) continue;
					if (count($params) !== count($route['params'])) continue;
					foreach ($params as $k => $v) {
						if (!array_key_exists($k, $route['params'])
								|| $route['params'][$k] != $v) {

							continue 2;
						}
					}
					return $alias;
				}
			}
		}

		return null;
	}

	public static function getFor($controller, $action = null, $params = null, $anchor = null) {

		if ($controller instanceof \eoko\module\executor\Executor) {
			$controller = "$controller->module.$controller";
		}

		$parts = array();

		if (is_array($controller)) {
			extract($controller);
		}

		if (null !== $alias = self::getRouteAlias($controller, $action, $params)) {
			if ($anchor !== null && substr($anchor, 0, 1) !== '#') $anchor = "#$anchor";
			return self::makeAbsolute("$alias$anchor");
		}

		if ($controller !== null) {
			$parts[] = "controller=$controller";
		}

		if ($action !== null) $parts[] = "action=$action";

		if ($params !== null) {
//			foreach ($params as $k => $v) $parts[] = "$k=$v";
			foreach ($params as $k => $v) $parts[] = urlencode($k) . '=' . urlencode($v);
		}

		$parts = implode('&', $parts);

		if ($anchor !== null) {
			$anchor = "#$anchor";
		}

		return self::makeAbsolute(
//			urlencode(
				"index.php?$parts$anchor"
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
