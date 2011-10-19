<?php

namespace eoko\modules\TreeMenu\ActionProvider;

use eoko\modules\TreeMenu\ActionProvider\ModuleProvider;
use eoko\module\Module;
use eoko\modules\TreeMenu\HasMenuActions;
use eoko\util\Arrays;

class ModuleGroupProvider extends ModuleProvider {
	
	/** @var array[Module] */
	private $children;
	/** @var Module */
	private $mainModule;

	public function __construct(Module $mainModule, $children) {
		parent::__construct($mainModule);
		$this->mainModule = $mainModule;
		$this->children = array();
		foreach ($children as $child) {
			if ($child instanceof HasMenuActions) {
				$this->children[] = $child;
			}
		}
	}
	
//	public function getIconCls($action = null) {
//		$r = parent::getIconCls($action);
//	}
	
	protected function buildMenuActionsData() {

		$moduleName = $this->mainModule->getName();
		$familyId = $this->getMenuFamilyId();
		$actions = parent::buildMenuActionsData();
		
		foreach ($this->children as $child) {
			
			$childName = $child->getName();
			$ap = $child->getActionProvider();
			$childActions = $ap->buildMenuActionsData();
			$childFamilyId = $ap->getSubFamilyId();
			
			if (isset($childActions['open'])) {
				$childActions['open']['command'] = "@$moduleName#open(,$childName)";
			}
			
			foreach ($childActions as $action) {
				$oldId = $action['id'];
				Arrays::apply($action, array(
					'id' => $id = "{$childFamilyId}_$oldId",
					'action_family' => $familyId,
					'baseIconCls' => $this->getIconCls($oldId, "$moduleName $childFamilyId"),
				));
				$actions[$id] = $action;
			}
		}
		
		return $actions;
		
		dump($actions);
		dump(parent::buildMenuActionsData());
	}
}