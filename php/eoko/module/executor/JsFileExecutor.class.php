<?php

namespace eoko\module\executor;

use eoko\file\FileType;
use eoko\util\Files;

use \Logger;

class JsFileExecutor extends ExecutorBase {

	protected function processResult($result) {}

	public function get_js() {
		return $this->get_module();
	}

	protected function getDefaultModule($name) {
		return false;
	}

	public function get_module() {
		$name = $this->request->req('name');

		Logger::dbg('js module is: {}', $name);

		if (!($path = $this->searchPath($name, FileType::JS))) {
			if (false === $this->getDefaultModule($name)) {
				Logger::get($this)->error('Cannot find js file for: "{}"', $name);
				return $this->answer404();
			} else {
				return;
			}
		}
//		require($path);
		echo rtrim(file_get_contents($path));

		foreach (array_merge(
			$this->module->listFiles(Files::regex("{$name}_*.js"), null, FileType::JS),
			$this->module->listFiles(Files::regex("*.js"), $name, FileType::JS)
		) as $plugin) {
			$cmtName = PHP_EOL . PHP_EOL . '// --- ' . basename($plugin) . ' ';
			echo $cmtName . str_repeat('-', 80 - strlen($cmtName)) . PHP_EOL;
//			require $plugin;
			$lines = file($plugin);
			foreach ($lines as $i => $line) {
				$line = rtrim($line);
				echo $line . str_repeat(' ', max(5, 80 - strlen($line))) . '// ' . ($i+1) . PHP_EOL;
			}
		}
	}
}