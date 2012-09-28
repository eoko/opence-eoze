<?php

namespace eoko\modules\ModuleGroupModule;

use eoko\_getModule\TabModule;

use eoko\module\ModuleManager;

use eoko\modules\TreeMenu\ActionProvider;
use eoko\modules\TreeMenu\ActionProvider\ModuleGroupProvider;
use eoko\modules\TreeMenu\HasMenuActions;

/**
 * Base module class for ModuleGroupModules. ModuleGroupModules are modules that 
 * opens as tab in their own main tab.
 * 
 * This module extends {@link eoko\modules\TabModule\TabModule} and inherits its
 * config options. In particular, the module title can be set in `config.tab.title`.
 * 
 * The modules to include are specified in the `config.modules` config option
 * (since this config option resides in the `config` node, it will also automatically
 * be made available to the javascript module -- see {@link eoko\modules\TabModule\TabModule}).
 * 
 * Example:
 * 
 *     # seasonMain.yml
 * 
 *     class: ModuleGroupModule
 *     
 *       config:
 *         tab:
 *           title: Saisons
 *     
 *         modules:
 *           - seasons
 *           - season_groups
 * 
 * 
 * Menu configuration
 * ------------------
 * 
 * The default ModuleGroupModule configuration provides a menu family for the
 * grouped modules.
 * 
 *     # ModuleGroupModule.menu.yml
 *		
 *     extra.menu:
 *  
 *       family:
 *         id: %module%
 *         label: %title%
 *     
 * As you see, the default action relies on the `module.title` config option to
 * be defined for the menu family label.
 * 
 * The ModuleGroupModule will add all existing actions of its children modules
 * in its menu family. The `open` action of a child module will open the group
 * module on the tab of this specific child.
 * 
 * The ModuleGroupModule default config doesn't provide its own open action since,
 * in most case, that would result in a doublon with the open action of the child
 * module which open as the default tab.
 * 
 * 
 * ### Disabling children modules own menu actions
 * 
 * The children modules actions are automatically added to the group family,
 * however the menu actions of the children module will **remain available** as
 * their own family, if no further configuration is done. To make a module 
 * unavailable as its own family in the menu, the `extra.menu.family` option 
 * should be set to false.
 * 
 * Disabling the chidlren modules own menu family is more than recommanded since,
 * in the current state of affairs, their open action will break the module
 * group by opening the child module in its own general tab...
 * 
 * If you need to customize the family id used in the iconCls menu item config
 * option, you can set the `extra.menu.subFamilyId` config option. If you don't
 * specify a custom subFamilyId, then it will defaults to the child module name
 * (which is great, in most case).
 * 
 * @package Modules\Eoko\ModuleGroupModule
 * @author Éric Ortéga <eric@planysphere.fr>
 */
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