<?php

namespace eoko\application;

use eoko\module\ModuleManager;

class BaseBootstrap extends Bootstrap {

	protected function initModulesLocations() {
		// instantiate the module manager, after the config paths have been
		// initialized
		ModuleManager::getInstance();
	}

	protected function registerModuleFactories() {}
	
	protected function initGlobalEvents() {}
}
