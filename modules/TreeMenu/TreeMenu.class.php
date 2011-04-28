<?php

namespace eoko\modules\TreeMenu;

use MenuNode, MenuNodeTable;

use eoko\module\Module;
use eoko\module\ModuleLocation;
use eoko\module\ModuleManager;

use eoko\cache\Cache;
use eoko\util\Arrays;
use eoko\log\Logger;

use eoko\modules\TreeMenu\HasMenuActions;
use eoko\modules\TreeMenu\MenuAction;
use eoko\modules\TreeMenu\MenuFamily;

use UserSession;
use IllegalStateException;

class TreeMenu extends Module {

	protected $defaultExecutor = 'json';

	public function getMenuFamilies() {

		if ($this->getConfig()->get('useCache', true)
				&& null !== $r = Cache::getCachedData(array($this, 'families'))) {
			return $r;
		}
		
		$r = array();
		foreach (ModuleManager::listModules() as $module) {
			if ($module instanceof HasMenuActions
					&& !$module->isAbstract()
					&& !$module->isDisabled()) {
				
				$family = $module->getFamily();
				
				if ($family !== null && $family !== false) {
					$r[$family->getId()] = $family;
				}
			}
		}
		
		Cache::cacheData(array($this, 'families'), $r);
		
		return $r;
	}
	
	public function invalidateCache() {
		Cache::clearCachedData(array($this, 'families'));
	}
	
	/**
	 * @param string $id
	 * @return MenuFamily
	 */
	public function getMenuFamily($id) {
		$families = $this->getMenuFamilies();
		if (!isset($families[$id])) {
			throw new IllegalStateException("Familiy $id doesn't exist");
		}
		return $families[$id];
	}
	
	/**
	 * @param string $action
	 * @return MenuAction
	 */
	public function getMenuAction($action) {
		if (count($parts = explode('.', $action)) !== 2) {
			throw new \IllegalArgumentException('Should be FAMILY.ACTION: ' . $action);
		}
		list($family, $action) = $parts;
		if (null !== $r = $this->getMenuFamily($family)->getAction($action)) {
			return $r;
		} else {
			throw new IllegalStateException("Family $family has no action $action");
		}
	}
	
	public function createDefaultMenu() {
		
		UserSession::requireLoggedIn();
		$userId = UserSession::getUser()->id;
		$items = $this->getConfig()->get('defaultMenu');
		
		$nodes = $this->createMenuItems($items);
		
		foreach ($nodes as $node) {
			$node->save();
		}
		
		return $nodes;
	}
	
	private function createMenuItem($name, $config) {
		
		UserSession::requireLoggedIn();
		$userId = UserSession::getUser()->id;
		
		$children = null;
		if (is_array($config)) {
			if (!Arrays::isAssoc($config)) {
				$children = $this->createMenuItems($config);
				$config = array();
			} else if (isset($config['children'])) {
				$children = $this->createMenuItems($config['children']);
				unset($config['children']);
			}
			
			$overrides = Arrays::applyIf($config, array(
				'id' => null,
				'label' => $name,
				'Children' => $children,
				'users_id' => $userId,
			));
			
			if (isset($config['action'])) {
				return $this->getMenuAction($config['action'])->createMenuNode($overrides);
			} else {
				return MenuNode::create($overrides);
			}
		
		} else if (is_string($config)) {
			return $this->getMenuAction($config)->createMenuNode(array(
				'id' => null,
				'users_id' => $userId,
			));
		
		} else if ($config === null) {
			if (!$name) {
				Logger::get($this)->warn('Empty menu configuration item is ignored');
			} else {
				return MenuNode::create(array(
					'id' => null,
					'label' => $name,
					'users_id' => $userId,
				));
			}
		}
//		
//		return Arrays::applyIf($config, array(
//			'label' => $name,
//			'children' => $children,
//		));
	}
	
	private function createMenuItems($items) {
		if (is_array($items)) {
			$r = array();
			if (Arrays::isAssoc($items)) {
				foreach ($items as $name => $config) {
					$r[] = $this->createMenuItem($name, $config);
				}
			} else {
				foreach ($items as $config) {
					$r[] = $this->createMenuItem(null, $config);
				}
			}
			
			// order
			foreach ($r as $i => $node) {
				$node->order = $i;
			}
			
			return $r;
		}
		
		throw new \IllegalStateException('Invalid configuration item in menu.yml');
	}
}