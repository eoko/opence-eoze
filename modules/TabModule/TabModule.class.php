<?php

namespace eoko\modules\TabModule;

use eoko\module\Module;

use eoko\util\Arrays;

use eoko\modules\TreeMenu\HasMenuActions;
use eoko\modules\TreeMenu\MenuFamily;
use eoko\modules\TreeMenu\MenuAction;

class TabModule extends Module implements HasMenuActions {
	
	protected $defaultExecutor = 'js';
	
	private $iconClass = null;
	private $menuFamily = null;
	private $menuActions = null;
	
	private function getPluginsConfig($key = null) {
		if (($config = $this->getConfig()->get('extra'))
				|| ($config = $this->getConfig()->get('plugins'))) {
			if ($key) {
				if (isset($config[$key])) return $config[$key];
				else return null;
			} else {
				return $config;
			}
		}
		return null;
	}
	
	public function getIconCls($action = null) {
		if (null !== $iconCls = $this->getPluginsConfig('iconCls')) {
			$iconCls = str_replace('%module%', $this->getName(), $iconCls);
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
	
	private function buildMenuActions() {
		// set cache to prevent recomputing
		$this->menuActions = array();
		if (null !== $config = $this->getMenuConfig()) {
			if (isset($config['actions'])) {
				$familyId = $this->getMenuFamilyId();
				$defaults = array(
					'family' => $familyId,
				);
				foreach ($config['actions'] as $action) {
					$this->menuActions[] = MenuAction::fromArray(
						Arrays::apply($defaults, $action)
					);
				}
			}
		}
		return $this->menuActions;
	}
	
	public function getAvailableActions() {
		if ($this->menuActions) return $this->menuActions;
		else return $this->buildMenuActions();
	}
	
	private function getMenuFamilyId() {
		if (null !== $config = $this->getMenuConfig()
				&& isset($config['family']['id'])) {
			return $config['family']['id'];
		} else {
			return $this->getName();
		}
	}
	
	private function buildMenuFamily() {
		if (null !== $config = $this->getMenuConfig()) {
			$defaults = array(
				'id' => $this->getName(),
				'label' => $this->getName(),
				'actions' => $this->getAvailableActions(),
			);
			// iconCls
			if (($iconCls = $this->getIconCls())) {
				$defaults['iconCls'] = $iconCls;
			}
			if (isset($config['family'])) {
				return $this->menuFamily = MenuFamily::fromArray(
					Arrays::apply($defaults, $config['family'])
				);
			} else if ($config !== null) {
				return $this->menuFamily = MenuFamily::fromArray($defaults);
			}
		} else {
			return false;
		}
	}

	public function getFamily() {
		if ($this->menuFamily) return $this->menuFamily;
		else return $this->buildMenuFamily();
	}
	
}