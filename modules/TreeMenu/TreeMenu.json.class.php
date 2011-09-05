<?php

namespace eoko\modules\TreeMenu;

use eoko\module\executor\JsonExecutor;
use MenuNode, MenuNodeTable;
use UserSession;

class Json extends JsonExecutor {
	
	public function loadUserMenu() {
		
		$finder = MenuNodeTable::find('`users_id`=?', $this->getUserId());
		$finder->query
				->orderBy('parent__menu_nodes_id')
				->thenOrderBy('order');
		$nodes = $finder->execute();
		
		if (!$nodes->count()) {
			$nodes = $this->getModule()->createDefaultMenu();
		}
		
		$data = array();
		foreach ($nodes as $node) {
			if ($node->parent__menu_nodes_id == null) {
				$data[] = $node->getData();
			}
		}
		
		$this->data = $data;
		
		return true;
	}
	
	public function getAvailableActions() {
		$module = $this->getModule();
		$families = array();
		foreach ($this->getModule()->getMenuFamilies() as $family) {
			if (null !== $data = $family->toArray(false, $module)) {
				$families[$family->getId()] = $data;
			}
		}
		uasort($families, function($f1, $f2) {
			$l1 = isset($f1['label']) ? $f1['label'] : null;
			$l2 = isset($f2['label']) ? $f2['label'] : null;
			return strnatcasecmp($l1, $l2);
		});
		$this->families = $families;
		return true;
	}

	public function resetFactoryDefaults() {
		UserSession::requireLoggedIn();
		$userId = UserSession::getUser()->id;

		MenuNodeTable::createQuery()
				->delete()
				->where('users_id=?', $this->getUserId())
				->execute();

		return true;
	}

	public function saveNode() {
		$data = $this->request->req('data');
		$this->id = $this->doSaveNode($data);
		return true;
	}

	private $userId = null;

	private function getUserId() {
		if ($this->userId !== null) return $this->userId;
		UserSession::requireLoggedIn();
		return $this->userId = UserSession::getUser()->id;
	}

	private function doSaveNode($data) {

		if (isset($data['children'])) {
			if ($data['full']) {
				$childIds = array();
				foreach ($data['children'] as $child) {
					$childIds[] = $this->doSaveNode($child);
				}
				$this->childrenIds = $childIds;
			}
			unset($data['children']);
		}

		if ($data['root']) {
			return 'root';
		}

		if (!$data['new']) {
			$node = MenuNode::load($data['id']);
			$node->setFields($data);
		} else {
			$node = MenuNode::create($data);
			$node->users_id = $this->getUserId();
			//dump("$node");
		}
		
		$node->saveManaged();

		return $node->id;
	}
	
	private function getIconFolderPath() {
		return EOZE_PATH . 'images' . DS . 'icons';
	}
	
	public function listIcons() {
		$iconProvider = $this->getModule()->getConfig()->get('iconProvider');
		if (!$iconProvider) {
			throw new \MissingConfigurationException(get_class($this), 'iconProvider');
		}
		$this->forward("$iconProvider.json", 'getIconList');
	}
	
	public function deleteNode() {
		return MenuNodeTable::delete($this->request->req('nodeId'));
	}
	
	public function clearCache() {
		$this->getModule()->invalidateCache();
	}
}