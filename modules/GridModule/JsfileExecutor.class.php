<?php

namespace eoko\modules\GridModule;

require_once dirname(__FILE__) . DS . 'gen' . DS . 'LegacyGridModule.class.php';

class JsFileExecutor extends \eoko\module\executor\JsFileExecutor {

	protected function getDefaultModule($name) {
		if ($name === $this->module->getName()) {
			\LegacyGridModule::generateModule($this->module)->render();
		} else {
			return false;
		}
	}
}