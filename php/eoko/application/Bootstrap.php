<?php

namespace eoko\application;

use eoko\config\ConfigManager;
use eoko\log\Logger;

abstract class Bootstrap {

	public function __invoke() {
		$this->initModulesLocations();
		$this->registerModuleFactories();
		$this->initGlobalEvents();
	}

	abstract protected function registerModuleFactories();

	abstract protected function initModulesLocations();
	
	abstract protected function initGlobalEvents();
}
