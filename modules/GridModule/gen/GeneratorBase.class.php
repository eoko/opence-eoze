<?php

namespace eoko\modules\GridModule\gen;

use \ModelTable;
use eoko\config\Config;

use eoko\module\ModuleManager;

abstract class GeneratorBase {
	
	/** @var Config */
	protected $config;
	protected $parentConfig;

	/** @var ModelTable */
	protected $table;

	/** @var Columns */
	protected $columns;

	public function  __construct($moduleName, Config $config, Config $parentConfig = null) {

		$this->config = $config;
		$this->parentConfig = $parentConfig !== null ? $parentConfig :
				$this->loadParentConfig();

		$modelName = $config->model;
		$this->table = ModelTable::getModelTable($modelName);;

		$this->columns = new Columns($moduleName, $this->config, $this->parentConfig, $this->table);
	}
	
	protected function loadParentConfig() {
		$config = ModuleManager::getModule($this->config->class)->getConfig();
		if (isset($config['class'])) {
			if ($config['class'] === null) {
				throw new \InvalidConfigurationException($file, $nodePath, $debugMessage, $message, $previous);
			} else {
				$parentConfig = clone ModuleManager::getModule($config['class'])->getConfig();
				$config = $parentConfig->apply($config, false);
			}
		}
		return $config;
	}

}