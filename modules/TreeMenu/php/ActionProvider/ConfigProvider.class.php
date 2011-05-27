<?php

namespace eoko\modules\TreeMenu\ActionProvider;

use eoko\modules\TreeMenu\ActionProvider;
use eoko\config\Config;

class ConfigProvider implements ActionProvider {
	
	private $menuFamily = null;
	private $menuActions = null;
	
	private $config = null;
	
	public function __construct(Config $config) {
		$this->config = $config;
	}
	
	private function getConfig() {
		return $this->config;
	}
	
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
	
	private function replacePlaceHolders(&$in) {
		if (is_array($in)) {
			foreach ($in as &$v) {
				$v = $this->replacePlaceHolders($in);
			}
			return $v;
		} else {
			return $v = str_replace('%module%', $this->getName(), $v);
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
					$action = Arrays::applyIf($action, $defaults);
					$action = $this->replacePlaceHolders($action);
					$this->menuActions[] = MenuAction::fromArray($action);
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
				$config = Arrays::apply($defaults, $config['family']);
				$config = $this->replacePlaceHolders($config);
			} else if ($config !== null) {
				$config = $this->replacePlaceHolders($defaults);
			}
			return $this->menuFamily = MenuFamily::fromArray($config);
		} else {
			return false;
		}
	}

	public function getFamily() {
		if ($this->menuFamily) return $this->menuFamily;
		else return $this->buildMenuFamily();
	}

}