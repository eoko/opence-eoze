<?php

namespace eoko\modules\AccessControl;

use eoko\module\Module;
use eoko\module\HasJavascript;

use eoko\file\FileType;
use eoko\template\Template;

class AccessControl extends Module implements HasJavascript {

	protected $defaultExecutor = 'login';

	public function getJavascriptAsString() {
		$path = $this->findPath('login', FileType::JS);
		return file_get_contents($path);
	}
}
