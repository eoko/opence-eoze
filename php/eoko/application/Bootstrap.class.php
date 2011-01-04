<?php

namespace eoko\application;

use eoko\config\ConfigManager;

abstract class Bootstrap {

	public function __invoke() {
		$this->initConfigPaths();
		$this->initModulesLocations();
		$this->registerModuleFactories();
	}

	protected static final function addConfigPath($path) {
		ConfigManager::addPath($path);
	}

	abstract protected function initConfigPaths();

	abstract protected function registerModuleFactories();

	abstract protected function initModulesLocations();
}