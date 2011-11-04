<?php

namespace eoko\modules\BaseModule;

use eoko\module\Module;

use eoko\module\HasTitle;
use eoko\modules\TreeMenu\HasMenuActions;
use eoko\modules\TreeMenu\ActionProvider\ModuleProvider;

class BaseModule extends Module implements HasTitle, HasMenuActions {
	
	private $actionProvider;
	
	public function getActionProvider() {
		if (!$this->actionProvider) {
			$this->actionProvider = new ModuleProvider($this);
		}
		return $this->actionProvider;
	}
	
	public function getTitle() {
		$config = $this->getConfig()->get('module');
		if (isset($config['title'])) {
			return $config['title'];
		} else {
			return null;
		}
	}
	
	public function getIconCls($action = null) {
		return $this->getActionProvider()->getIconCls($action);
	}
}