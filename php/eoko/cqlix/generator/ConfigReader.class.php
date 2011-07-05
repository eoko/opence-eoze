<?php

namespace eoko\cqlix\generator;

use eoko\util\Files;

use InvalidConfigurationException;

class ConfigReader extends \eoko\config\ConfigReader {

	protected function process(&$content) {
		foreach ($content as &$loc) {

			if (!isset($loc['extends'])) {
				throw new InvalidConfigurationException();
			}

			foreach ($loc['extends'] as &$ex) {
				if (count($parts = explode('|', $ex)) > 1) {
					foreach ($parts as $p) {
						$p = trim($p);
						if (class_exists($p)) {
							$ex = $p;
							break;
						}
					}
				}
			}
		}
		return parent::process($content);
	}
}
