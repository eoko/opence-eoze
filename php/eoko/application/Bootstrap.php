<?php

namespace eoko\application;

use eoko\config\ConfigManager;
use eoko\log\Logger;

abstract class Bootstrap {

	private static $currentBootstrap = null;

	/**
	 * @return Bootstrap
	 */
	public static function getCurrent() {
		return self::$currentBootstrap;
	}

	public function __invoke() {

		self::$currentBootstrap = $this;

		$this->initModulesLocations();
		$this->registerModuleFactories();
		$this->initGlobalEvents();
	}

	abstract protected function registerModuleFactories();

	abstract protected function initModulesLocations();

	abstract protected function initGlobalEvents();
}
