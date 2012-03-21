<?php

namespace eoko\modules\GridModule;

use eoko\module\Module;
use eoko\module\HasTitle;
use eoko\module\ModulesLocation;
use eoko\template\PHPCompiler;
use eoko\php\generator\ClassGeneratorManager;
use eoko\cache\Cache;

use \ModelTable;

// TODO: the Module.tpl.php template is not used anymore... clean out all
// references to that

//dump_trace();

class GridModule extends Module implements HasTitle {
	
	private $codeTemplatePath = 'php-template/';
	
	protected $defaultExecutor = 'grid';
	
	public function generateGridExecutorBase($namespace, $class, $baseClass) {

		$tpl = PHPCompiler::create()->setFile(
			$this->findPath($this->codeTemplatePath . 'GridExecutor.tpl.php')
		);

		$tpl->namespace = $namespace;
		$tpl->class     = $class;
		$tpl->extend    = $baseClass;
		
		$config = $this->getConfig();
		$modelName = $config->model;
		$tpl->tableName = ModelTable::getModelTable($modelName);

		$gen = new gen\ExecutorGenerator($this->name, $config);
		$gen->populate($tpl);

		return $tpl;
	}
	
	public function getTitle() {
		$config = $this->getConfig()->module;
		if (isset($config['title'])) {
			return $config['title'];
		} else {
			return null;
		}
	}
	
}