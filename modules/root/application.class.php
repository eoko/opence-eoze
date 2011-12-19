<?php

namespace eoko\modules\root;

use eoko\module\executor\JsonExecutor;

class Application extends JsonExecutor {
	
	public function index() {
		return $this->forward('root.bootstrap', 'get_js', array('name' => 'MainApplication.mod'));
	}
	
	public function configure() {
		$this->instanceId = uniqid();
		return true;
	}
}