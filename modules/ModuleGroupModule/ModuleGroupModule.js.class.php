<?php

namespace eoko\modules\ModuleGroupModule;

use eoko\module\ModuleManager;

class Js extends JsBase {
	
	public function get_module() {
		
		$module = $this->getModule();
		$config = $module->getConfig()->get('config');
		
		$modules = array();
		
		foreach ($config['modules'] as $module) {
			$module = ModuleManager::getModule($module);
			$moduleName = $module->getName();
			if (method_exists($module, 'getTitle')) {
				$title = $module->getTitle();
			} else {
				$title = $moduleName;
			}
			$modules[] = json_encode(array(
				'title' => $title,
				'name' => $moduleName,
				'cmd' => "Oce.Modules.$moduleName.$moduleName",
			));
		}
		
		$tpl = parent::get_module();
		$tpl->main->modules = $modules;
		return $tpl;
	}
}