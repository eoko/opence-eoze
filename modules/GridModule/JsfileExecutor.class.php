<?php

namespace eoko\modules\GridModule;

require_once __DIR__ . DS . 'gen' . DS . 'LegacyGridModule.php';

class JsFileExecutor extends \eoko\module\executor\JsFileExecutor {

	protected function getDefaultModule($name) {
		if ($name === $this->module->getName()) {
			\LegacyGridModule::generateModule($this->module)->render();
		} else {
			return false;
		}
	}
}