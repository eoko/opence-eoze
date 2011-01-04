<?php

namespace eoko\modules\root;

use eoko\module\executor\JsonExecutor;

class Application extends JsonExecutor {
	
	public function index() {
		return $this->forward('root.bootstrap', 'get_js', array('name' => 'MainApplication.mod'));
	}
	
	public function configure() {
		if (class_exists('myState')) {
			throw new \UnsupportedOperationException('Must be modularized!!!');
		}
		// The following has been commented out, since it is specific to the
		// Rhodia application (but this application should implement it otherwise,
		// or it is broken!

//		$this->config = array(
//			'year' => \State::getCurrentYear()
//		);
		return true;
	}
}