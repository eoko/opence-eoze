<?php

use eoko\config\Config;

class ConfigInheritor {

	private static $instance = null;

	protected static $configs = array();

	protected function getNode($name, &$nodeClass) {
		$parts = explode(':', $name);
		list($configItem, $nodePath) = $parts;

		if (isset(self::$configs[$configItem])) $config = self::$configs[$configItem];
		else $config = self::$configs[$configItem] = Config::load($configItem);

		$leftParts = explode('/', $parts[0]);
		$nodeClass = $leftParts[0];

		return $config->node($nodePath, true);
	}

	public static function process(&$config, $inheritanceProvider) {
		if (!isset($config['from'])) return false;

		$from = $config['from'];
		unset($config['from']);

		if (!is_array($from)) {
			self::processFrom($config, $from, $inheritanceProvider);
		} else {
			foreach ($from as $parent) self::processFrom($config, $parent, $inheritanceProvider);
		}

		Config::load($from);
	}

	protected static function processFrom(&$config, $from, $inheritanceProvider) {

		$fromNode = self::getNode($from, $nodeClass);

		$convertedFrom = $inheritanceProvider->convertInheritance($from, $nodeClass);

		$config = ArrayHelper::apply($convertedFrom, $config);
	}

}