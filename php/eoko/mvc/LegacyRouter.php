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

//			$content = $response->getContent();
//			$response->getHeaders()->addHeaderLine('ETag: ' . md5($content));
//			$response->sendHeaders();
//			// TODO 20131220-145849
//
//			/*
//			 * The last available version of pecl_http seems broken. Using version 1.7.6
//			 * works... (see: http://forums.bitcasa.com/index.php?/topic/575-pecl-http-on-ubuntu-1204/)
//			 *
//			 *     sudo pecl uninstall pecl_http
//			 *     sudo pecl install pecl_http-1.7.6
//			 */
//			http_send_data($content);

			// sudo apt-get install libapache2-mod-xsendfile
//			$tmp = tmpfile();
//			fwrite($tmp, $content);
//			http_send_stream($tmp);
//			fclose($tmp);
		}
		return $response;
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
