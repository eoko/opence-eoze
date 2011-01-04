<?php

namespace eoko\application;

use eoko\module\ModuleManager;

class BaseBootstrap extends Bootstrap {

	protected function initConfigPaths() {
		self::addConfigPath(CONFIG_PATH);
	}

	protected function initModulesLocations() {
		// instanciate the module manager, after the config paths have been
		// initialized
		ModuleManager::getInstance();
	}

	protected function registerModuleFactories() {}
}
