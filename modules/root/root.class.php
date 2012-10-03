<?php

namespace eoko\modules\root;

use eoko\module\Module;
use eoko\module\HasJavascript;
use eoko\module\traits\HasRoutes;

use eoko\file\FileType;

class root extends Module implements HasJavascript, HasRoutes {
	
	protected $defaultExecutor = 'bootstrap';

	public function getJavascriptAsString() {
		$path = $this->searchPath('ApplicationBootstrap', FileType::JS);
		return rtrim(file_get_contents($path));
	}
	
	public function getRoutesConfig() {
		$config = $this->getConfig();
		return isset($config['router'])
				? $config['router']
				: null;
	}
}
