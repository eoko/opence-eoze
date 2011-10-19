<?php

namespace eoko\module\executor;

use eoko\template\JsonTemplate;

use \ExtJSResponse;

class JsonExecutor extends TemplateExecutor {
	
	/**
	 * @return JsonTemplate
	 */
	protected function getResponse() {
		return $this->getTemplate();
	}

	protected function createTemplate($name, $require = true, $opts = null) {
		if ($name !== null) {
			throw new UnsupportedOperationException(get_class($this) . '::createTemplate($name)');
		} else {
			return new JsonTemplate($opts);
		}
	}
	
	protected function processResult($result) {
		if (is_bool($result)) {
			$tpl = $this->getTemplate();
			$tpl->mergeWithWarning(ExtJSResponse::toArray(), $this);
			$tpl->success = $result;
			$tpl->render();
		}
	}

}