<?php

namespace eoko\mvc;

use eoko\module\ModuleResolver;
use eoko\module\executor\Executor;
use Zend\Http\PhpEnvironment\Response;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 1 août 2012
 */
class LegacyRouter extends AbstractRouter {

	public function route() {
		$response = $this->getResponse();
		if ($response instanceof Response) {
			$this->setResponseDefaultContent($response);
			$response->send();
		}
	}

	/**
	 * Gets the Response from the routed action.
	 *
	 * @return Response|mixed
	 */
	protected function getResponse() {

		$action = ModuleResolver::parseRequestAction($this->requestData);

		if ($action instanceof Executor) {
			$action->setRouter($this->router);
		}

		return $action();
	}

	/**
	 * Sets the default response content if it is empty and the response indicates an error.
	 *
	 * @param Response $response
	 */
	private function setResponseDefaultContent(Response $response) {
		// if content is empty
		if ($response->getContent() === '') {
			// if error
			if ($response->isClientError() || $response->isServerError()) {
				$code = $response->getStatusCode();
				$reason = $response->getReasonPhrase();
				$response->setContent("<h1>Error $code: $reason</h1>");
			}
		}
	}
}
