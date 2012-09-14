<?php

namespace eoko\modules\root;

use eoko\module\Module;
use eoko\module\HasJavascript;

use eoko\file\FileType;

class root extends Module implements HasJavascript {
	
	protected $defaultExecutor = 'bootstrap';

	public function getJavascriptAsString() {
		$path = $this->searchPath('ApplicationBootstrap', FileType::JS);
		return rtrim(file_get_contents($path));
	}
}