<?php

namespace eoko\modules\root;

use eoko\module\Module;
use eoko\module\traits\HasRoutes;

class root extends Module implements HasRoutes {
	
	protected $defaultExecutor = 'bootstrap';
	
	public function getRoutesConfig() {
		$config = $this->getConfig();
		return isset($config['router'])
				? $config['router']
				: null;
	}
}
