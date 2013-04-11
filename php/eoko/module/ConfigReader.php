<?php

namespace eoko\module;

use eoko\util\Files;

class ConfigReader extends \eoko\config\ConfigReader {

	protected function process(&$content) {
		if (isset($content['locations'])) {
			foreach ($content['locations'] as $name => &$location) {
				$this->processLocation($location);
			}
			unset($location);
		}
		if (isset($content['used'])) {
			foreach ($content['used'] as $location => &$v) {
				if ($v === 'all') $v = '*';
			}
		}
		return parent::process($content);
	}

	private function processLocation(&$location) {
		if (isset($location['path'])) {
			$path =& $location['path'];
//			$path = str_replace('%lib%', PHP_PATH . 'eoko', $path);
			$path = str_replace('%eoze%', EOZE_PATH, $path);
			$path = str_replace('/', DS, $path);
			$path = str_replace(DS . DS, DS, $path);
			if (!Files::isAbsolute($path)) {
				$path = ROOT . $path;
			}
			if (substr($path, -1) !== DS) $path .= DS;
		}
		if (isset($location['url'])) {
			$url =& $location['url'];
//			$url = str_replace(array('%lib%', '%eoze%'), LIB_PHP_BASE_URL . 'eoko/', $url);
			$url = str_replace('%eoze%', EOZE_BASE_URL, $url);
			$url = preg_replace('@([^:/])//@', '$1/', $url);
			if (!strstr($url, '://')) {
				$url = SITE_BASE_URL . $url;
			}
			if (substr($url, -1) !== '/') $url .= '/';
		}
	}
}