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
		// Hack upon legacy
		if (is_array($result)) {
			$this->getTemplate()->merge($result);
			$result = true;
		}
		// Legacy

		/** @var \Zend\Http\PhpEnvironment\Response $response  */
		$response = $this->getResponse();

		if (is_bool($result)) {
			$tpl = $this->getTemplate();
			$tpl->mergeWithWarning(ExtJSResponse::toArray(), $this);
			$tpl->set('success', $result);
			if ($return) {
				return $this->getData();
			} else {
				$data = $this->getData();

				$headers = $response->getHeaders();

				if (isset($data['timestamp'])) {
					$headers->addHeaderLine('X-Eoze-Request-Timestamp: ' . $data['timestamp']);
					$this->__unset('timestamp');
				}

				if (isset($data['requestId'])) {
					$headers->addHeaderLine('X-Eoze-Request-Id: ' . $data['requestId']);
					$this->__unset('requestId');
				}

				$response->setContent($tpl->render(true));
			}
		}

		return $response;
	}

	/**
	 * Multipurpose test action.
	 *
	 * @return bool
	 */
	public function ping() {
		$this->set('time', time());
		return true;
	}

}
