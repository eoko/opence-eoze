<?php

namespace eoko\modules\TabModule;

use eoko\template\Template;

use SystemException;

class Js extends \eoko\module\executor\ExecutorBase {
	
	protected function processResult($result) {
		if (is_string($result)) {
			require $result;
		} else if ($result instanceof Template) {
			$result->render();
		} else if ($result === false) {
			throw new SystemException('Unknown error');
		}
	}
	
	public function get_module() {
		if (!headers_sent()) {
			header('Content-type: application/javascript');
		}
		return $this->getModule()->createModuleJavascriptTemplate();
	}
}