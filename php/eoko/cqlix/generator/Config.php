<?php

namespace eoko\cqlix\generator;

use eoko\config\ConfigManager;
use eoko\util\Files;
use eoko\util\Arrays;
use eoko\util\YmlReader;

class Config extends \eoko\config\Config {

	/**
	 * @var Config
	 */
	private $values;

	public function __construct() {
		$this->loadBaseConfig();
	}

	private function loadBaseConfig() {
//		$configPath = MODEL_PATH . 'config' . DS;
//		$baseConfigFilename = '/^base_?(\d*)\.yml$/';
//
//		$files = array();
//		foreach (Files::listFiles($configPath, $baseConfigFilename) as $filename) {
//			if (preg_match($baseConfigFilename, $filename, $m) && isset($m[1])) {
//				$index = $m[1];
//			} else {
//				$index = $filename;
//			}
//			$files[$index] = "$configPath$filename";
//		}
//
//		ksort($files);
//
//		$config = array();
//		foreach ($files as $file) {
//			$cfg = YmlReader::loadFile($file);
//			Arrays::apply($config, $cfg, false);
//		}
//		$this->values = $config;

		$content = ConfigManager::get(get_namespace($this));

		// --- Legacy code from eoko\cqlix\ConfigReader
		foreach ($content as &$loc) {

			if (!isset($loc['extends'])) {
				throw new \InvalidConfigurationException();
			}

			foreach ($loc['extends'] as &$ex) {
				if (count($parts = explode('|', $ex)) > 1) {
					$items = array();
					foreach ($parts as $p) {
						$items[] = trim($p);
					}
					$ex = $items;
				}
			}
		}
		// ---

		$this->values = $content;
	}

	public function buildModelInfo($dbTable) {
		foreach ($this->values as $k => $cfg) {
			if (isset($cfg['applies']) && preg_match($cfg['applies'], $dbTable, $m)) {
				$baseName = isset($m[1]) ? $m[1] : $m[0];
				return $this->doBuildModelInfo($cfg, $dbTable, $baseName);
			}
		}
		return null;
	}

	private function doBuildModelInfo($config, $dbTable, $baseName) {

		$r = array(
			'dbTable' => $dbTable,
			'modelName' => NameMaker::modelFromDB($baseName),
			'tableName' => NameMaker::tableFromDB($baseName),
		);

		$extends = array('Model', 'Table', 'Query', 'Proxy');
		$tmp = array();
		foreach ($extends as $v) {
			$tmp[strtolower($v)] = $v;
		}
		$types = $extends = $tmp;
		if (isset($config['extends'])) {
			$ex = $config['extends'];
			if (is_string($ex)) {
				if (!strstr($ex, '%s')) {
					$ex .= '%s';
				}
				foreach ($extends as $type => &$name) {
					$name = sprintf($ex, $type);
				}
			} else if (is_array($ex)) {
				foreach ($extends as $type => &$name) {
					if (isset($ex[$type])) {
						if (is_array($ex[$type])) {
							foreach ($ex[$type] as $exItem) {
								if (class_exists($exItem)) {
									$name = $exItem;
									break;
								}
							}
						} else {
							$name = $ex[$type];
						}
					}
				}
			} else {
				throw new \IllegalStateException();
			}
		}

		$r['extends'] = $extends;
		foreach ($extends as $type => $class) $r["base$types[$type]Name"] = $class;

		return $r;
	}

}
