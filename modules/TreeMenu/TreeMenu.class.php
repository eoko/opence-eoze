<?php

namespace eoko\modules\TreeMenu;

use MenuNode, MenuNodeTable;

use eoko\module\Module;
use eoko\module\ModuleLocation;
use eoko\module\ModuleManager;

use eoko\cache\Cache;
use eoko\util\Arrays;
use eoko\log\Logger;

use eoko\modules\TreeMenu\HasAccessLevel;
use eoko\modules\TreeMenu\HasMenuActions;
use eoko\modules\TreeMenu\MenuAction;
use eoko\modules\TreeMenu\MenuFamily;

use UserSession;
use Exception, IllegalStateException, IllegalArgumentException;

class TreeMenu extends Module implements HasMenuActions {

	protected $defaultExecutor = 'json';
	
	private $actionProvider;
	
	protected function setPrivateState(&$vals) {
		$this->actionProvider = $vals['actionProvider'];
		unset($vals['actionProvider']);
		parent::setPrivateState($vals);
	}
	
	public function getMenuFamilies() {
		
		$cacheKey = array($this, 'families');
		
		$useCache = $this->getConfig()->get('useCache', false);

		if ($useCache 
				&& null !== $r = Cache::getCachedData($cacheKey)) {
			return $r;
		}
		
		$r = array();
		foreach (ModuleManager::listModules() as $module) {
			if ($module instanceof HasMenuActions
					&& !$module->isAbstract()
					&& !$module->isDisabled()) {
				
				$family = $module->getActionProvider()->getFamily();
				
				if ($family !== null && $family !== false) {
					$r[$family->getId()] = $family;
				}
			}
		}

		// don't lose time caching for nada...
		if ($useCache) {
			Cache::cacheData($cacheKey, $r);
		}
		
		return $r;
	}
	
	public function invalidateCache() {
		Cache::clearCachedData(array($this, 'families'));
	}
	
	/**
	 * @param string $id
	 * @return MenuFamily
	 * @throws MissingMenuFamilyException
	 */
	public function getMenuFamily($id) {
		$families = $this->getMenuFamilies();
		if (!isset($families[$id])) {
			throw new MissingMenuFamilyException("Familiy '$id' doesn't exist");
		}
		return $families[$id];
	}
	
	/**
	 * @param string $action
	 * @return MenuAction
	 * @throws IllegalArgumentException
	 * @throws MissingMenuActionException
	 * @throws MissingMenuFamilyException
	 */
	public function getMenuAction($action) {
		if (count($parts = explode('.', $action)) !== 2) {
			throw new IllegalArgumentException('Should be FAMILY.ACTION: ' . $action);
		}
		list($family, $action) = $parts;
		if (null !== $r = $this->getMenuFamily($family)->getAction($action)) {
			return $r;
		} else {
			throw new MissingMenuActionException("Family '$family' has no action '$action'");
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
				try {
					return $this->getMenuAction($config['action'])->createMenuNode($overrides);
				} catch (MissingMenuElementException $ex) {
					Logger::get($this)->warn('Missing menu item is broken: {}', $ex->getMessage());
					$overrides['cssClass'] = 'error';
					return MenuNode::create($overrides);
				}
			} else {
				return MenuNode::create($overrides);
			}
		
		} else if (is_string($config)) {
			try {
				return $this->getMenuAction($config)->createMenuNode(array(
					'id' => null,
					'users_id' => $userId,
				));
			} catch (MissingMenuElementException $ex) {
				Logger::warn('Missing menu item is ignored: {}', $ex->getMessage());
			}
		
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
					try {
						$r[] = $this->createMenuItem($name, $config);
					} catch (Exception $ex) {
						Logger::error('Error creating menu item', $ex);
					}
				}
			} else {
				foreach ($items as $config) {
					try {
						$r[] = $this->createMenuItem(null, $config);
					} catch (Exception $ex) {
						Logger::error('Error creating menu item', $ex);
					}
				}
			}
			
			// order
			foreach ($r as $i => $node) {
				$node->order = $i;
			}
			
			return $r;
		}
		
		throw new IllegalStateException('Invalid configuration item in menu.yml');
	}
	
	public function isAuthorized(HasAccessLevel $item) {
		return true;
	}
	
	public function getActionProvider() {
		if (!$this->actionProvider) {
			$this->actionProvider = new ActionProvider\ModuleProvider($this);
		}
		return $this->actionProvider;
	}

}

class MissingMenuElementException extends Exception {
	
}

class MissingMenuFamilyException extends MissingMenuElementException {
	
}

class MissingMenuActionException extends MissingMenuElementException {
	
}