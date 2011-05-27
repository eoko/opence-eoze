<?php

namespace eoko\modules\TabModule;

use eoko\module\Module;
use eoko\module\HasTitle;
use eoko\modules\TreeMenu\HasMenuActions;
use eoko\modules\TreeMenu\ActionProvider\ModuleProvider;

class TabModule extends Module implements HasTitle, HasMenuActions {
	
	protected $defaultExecutor = 'js';
	
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
	
}