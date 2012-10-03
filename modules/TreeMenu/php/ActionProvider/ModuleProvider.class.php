<?php

namespace eoko\modules\TreeMenu\ActionProvider;

use eoko\modules\TreeMenu\ActionProvider;
use eoko\modules\TreeMenu\MenuAction;
use eoko\modules\TreeMenu\MenuFamily;
use eoko\module\Module;
use eoko\module\HasTitle;
use eoko\config\Config;
use eoko\util\Arrays;

class ModuleProvider implements ActionProvider {
	
	private $menuFamily = null;
	private $menuActions = null;

	/** @var Module */
	private $module = null;
	
	public function __construct(Module $module) {
		$this->module = $module;
	}
	
	private function getModuleConfig() {
		return $this->module->getConfig();
	}
	
	private function getModuleTitle() {
		if ($this->module instanceof HasTitle) {
			return $this->module->getTitle();
		} else {
			return null;
		}
	}
	
	private function getModuleName() {
		return $this->module->getName();
	}
	
	private function getModuleTitleOrName() {
		if (null !== $r = $this->getModuleTitle()) {
			return $r;
		} else {
			return $this->getModuleName();
		}
	}
	
	private function getPluginsConfig($key = null) {
		if (($config = $this->getModuleConfig()->get('extra'))
				|| ($config = $this->getModuleConfig()->get('plugins'))) {
			if ($key) {
				if (isset($config[$key])) return $config[$key];
				else return null;
			} else {
				return $config;
			}
		}
		return null;
	}
	
	public function getIconCls($action = null, $module = null) {
		if ($module === null) {
			$module = $this->getModuleName();
		}
		if (null !== $iconCls = $this->getPluginsConfig('iconCls')) {
			$iconCls = str_replace('%module%', $module, $iconCls);
			if ($action !== null) {
				if ($action === false) $action = '';
				$iconCls = str_replace('%action%', $action, $iconCls);
			}
			return $iconCls;
		}
	}

	/**
	 * @return array
	 */
	private function getMenuConfig() {
		if (null !== $config = $this->getPluginsConfig('menu')) {
			return $config;
		}
	}
	
	private function replacePlaceHolders(&$in) {
		if (is_array($in)) {
			foreach ($in as &$v) {
				$v = $this->replacePlaceHolders($v);
			}
			return $in;
		} else if (is_string($in)) {
			$in = str_replace('%module%', $this->getModuleName(), $in);
			$in = str_replace('%title%', $this->getModuleTitle(), $in);
			return $in;
		} else {
			return $in;
		}
	}
	
	private function buildMenuActions() {
		$menuActions = array();
		foreach ($this->buildMenuActionsData() as $id => $action) {
			$menuActions[$id] = MenuAction::fromArray($action);
		}
		return $menuActions;
	}
	
	protected function buildMenuActionsData() {
		$menuActions = array();
		if (null !== $config = $this->getMenuConfig()) {
			if (isset($config['actions'])) {
				$familyId = $this->getMenuFamilyId();
				$defaults = array(
					'action_family' => $familyId,
				);
				foreach ($config['actions'] as $name => $action) {
					if ($action === false) {
						continue;
					}
					$action = Arrays::applyIf($action, $defaults);
					$action = $this->replacePlaceHolders($action);
					if (!isset($action['id']) && is_string($name)) {
						$action['id'] = $name;
					}
//					$menuActions[$action['id']] = MenuAction::fromArray($action);
					$menuActions[$action['id']] = $action;
				}
			}
		}
		return $menuActions;
	}
	
	public function getAvailableActions() {
		if ($this->menuActions) {
			return $this->menuActions;
		} else {
			return $this->menuActions = $this->buildMenuActions();
		}
	}
	
	protected function getMenuFamilyId() {
		if (null !== $config = $this->getMenuConfig()) {
			if (isset($config['family']['id'])) {
				return $config['family']['id'];
			} else if (isset($config['familyId'])) {
				return $config['familyId'];
			}
		}
		// defaults to module name
		return $this->getModuleName();
	}
	
	protected function getSubFamilyId() {
		if ((null !== $config = $this->getMenuConfig())
				&& isset($config['subFamilyId'])) {
			return $config['subFamilyId'];
		}
		// defatuls to familyId
		return $this->getMenuFamilyId();
	}
	
	private function buildMenuFamily() {
		if (null !== $config = $this->getMenuConfig()) {
			$defaults = array(
				'id' => $this->getMenuFamilyId(),
				'label' => $this->getModuleTitleOrName(),
				'actions' => $this->getAvailableActions(),
			);
			// iconCls
			if (($iconCls = $this->getIconCls())) {
				$defaults['iconCls'] = $iconCls;
			}
			if (isset($config['family'])) {
				if ($config['family'] === false) {
					return false;
				} else {
					$defaults = Arrays::apply($defaults, 
							$this->replacePlaceHolders($config['family']));
				}
			}
			return MenuFamily::fromArray($defaults);
		} else {
			return false;
		}
	}

	public function getFamily() {
		if ($this->menuFamily) return $this->menuFamily;
		else return $this->menuFamily = $this->buildMenuFamily();
	}

}