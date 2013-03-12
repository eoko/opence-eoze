<?php

namespace eoko\module\executor;

use eoko\template\JsonTemplate;
use Zend\Http\Response;

use ExtJSResponse;
use UnsupportedOperationException;

class JsonExecutor extends TemplateExecutor {

	protected function createTemplate($name, $require = true, $opts = null) {
		if ($name !== null) {
			throw new UnsupportedOperationException(get_class($this) . '::createTemplate($name)');
		} else {
			return new JsonTemplate($opts);
		}
	}

	protected function processResult($result, $return = false) {

		/** @var \Zend\Http\PhpEnvironment\Response $response  */
		$response = $this->getResponse();

		if (is_bool($result)) {
			$tpl = $this->getTemplate();
			$tpl->mergeWithWarning(ExtJSResponse::toArray(), $this);
			$tpl->set('success', $result);
			if ($return) {
				return $this->getData();
			} else {
				$response->setContent($tpl->render(true));
			}
		}

		return $response;
	}

}
