<?php

namespace eoko\application;

use eoko\config\ConfigManager;
use eoko\log\Logger;

abstract class Bootstrap {

	public function __invoke() {
		$this->initConfigPaths();
		$this->initModulesLocations();
		$this->registerModuleFactories();
	}

	protected static final function addConfigPath($path) {
		if (is_dir($path)) {
			ConfigManager::addPath($path);
		} else {
			Logger::get(get_called_class())->warn((file_exists($path) ? "$path does not exist ("
				: "$path is not a directory")
				. ' (cannot be added as config path -- Bootstrap::addConfigPath)');
		}
	}

	abstract protected function initConfigPaths();

	abstract protected function registerModuleFactories();

	abstract protected function initModulesLocations();
}