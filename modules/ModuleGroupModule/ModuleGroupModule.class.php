<?php

namespace eoko\modules\ModuleGroupModule;

use eoko\_getModule\TabModule;

use eoko\module\ModuleManager;

use eoko\modules\TreeMenu\ActionProvider;
use eoko\modules\TreeMenu\ActionProvider\ModuleGroupProvider;
use eoko\modules\TreeMenu\HasMenuActions;

class ModuleGroupModule extends TabModule {

	public function getActionProvider() {
		$config = $this->getConfig()->get('config');
		
//		$actions = array();

		$children = array();
		foreach ($config['modules'] as $module) {
			$children[] = ModuleManager::getModule($module);
//			$name = $module->getName();
//			if (!($module instanceof HasMenuActions)) {
//				continue;
//			}
//			$moduleActions = $module->getActionProvider()->getAvailableActions();
//			unset($moduleActions['open']);
//			
//			foreach ($moduleActions as $action) {
//				$actions["{$module->getName()}_{$action->getId()}"] = $action;
//			}
		}

		return new ModuleGroupProvider($this, $children);
	}
	
	protected function getChildModules() {
		$config = $this->getConfig()->get('config');
		$r = array();
		if (isset($config['modules'])) {
			foreach ($config['modules'] as $module) {
				$r[] = ModuleManager::getModule($module);
			}
		}
		return $r;
	}
	
}