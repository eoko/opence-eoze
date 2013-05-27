<?php
/**
 * Copyright (C) 2013 Eoko
 *
 * This file is part of Opence.
 *
 * Opence is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Opence is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Opence. If not, see <http://www.gnu.org/licenses/gpl.txt>.
 *
 * @copyright Copyright (C) 2013 Eoko
 * @licence http://www.gnu.org/licenses/gpl.txt GPLv3
 * @author Éric Ortega <eric@eoko.fr>
 */

namespace eoko\modules\EozeExt4\controller;

use eoko\modules\EozeExt4\Exception;
use Zend\Http\PhpEnvironment\Request as HttpRequest;
use Zend\Http\Response;
use eoko\module\executor\JsonExecutor;

/**
 * Abstract scaffolding for a RESTFul executor.
 *
 * @since 2013-04-18 10:26
 */
abstract class Rest extends JsonExecutor {

	/**
	 * Gets the HTTP request object.
	 *
	 * @return HttpRequest
	 */
	protected function getHttpRequest() {
		return $this->getRequest()->getHttpRequest();
	}

	/**
	 * Log the passed exception.
	 *
	 * @param \Exception $ex
	 */
	abstract protected function logException(\Exception $ex);

	/**
	 * Action for listing records.
	 *
	 * @throws Exception\UnsupportedOperation
	 * @return bool|Response
	 */
	public function getList() {
		throw new Exception\UnsupportedOperation('TODO');
	}

	/**
	 * Action for deleting record(s).
	 *
	 * @param mixed $id
	 * @throws Exception\UnsupportedOperation
	 * @return bool|Response
	 */
	public function deleteRecord(/** @noinspection PhpUnusedParameterInspection */ $id = null) {
		throw new Exception\UnsupportedOperation('TODO');
	}

	/**
	 * Action for posting new data, that is creating a new record.
	 *
	 * @param array $inputData
	 * @throws Exception\UnsupportedOperation
	 * @return bool|Response
	 */
	public function postRecord(/** @noinspection PhpUnusedParameterInspection */ $inputData = null) {
		throw new Exception\UnsupportedOperation('TODO');
	}

	/**
	 * Action for updating existing records with no other side effect. It is required that this method
	 * can be executed multiple times with the same effect as executing it only one time (i.e. the
	 * action must be idempotent).
	 *
	 * @param null $id
	 * @param null $inputData
	 * @throws Exception\UnsupportedOperation
	 * @return bool|Response
	 */
	public function putRecord(/** @noinspection PhpUnusedParameterInspection */
			$id = null, $inputData = null) {
		throw new Exception\UnsupportedOperation('TODO');
	}

	/**
	 * Action for reading a record's data.
	 *
	 * @param mixed $id
	 * @throws Exception\UnsupportedOperation
	 * @return bool|Response
	 */
	public function getRecord(/** @noinspection PhpUnusedParameterInspection */ $id = null) {
		throw new Exception\UnsupportedOperation('TODO');
	}

	/**
	 * Index action.
	 *
	 * @return bool|Response
	 * @throws Exception\UnsupportedOperation
	 */
	public function index() {

		$httpRequest = $this->getHttpRequest();
		$method = $httpRequest->getMethod();

		$request = $this->getRequest();
		$response = $this->getResponse();

		$id = $request->get('id', null);

		try {
			if ($id) {
				if ($method === $httpRequest::METHOD_GET) {
					return $this->getRecord($id);
				} else if ($method === $httpRequest::METHOD_PUT || $method === $httpRequest::METHOD_POST) {
					$inputData = $request->req('data');
					return $this->putRecord($id, $inputData);
				} else if ($method === $httpRequest::METHOD_DELETE) {
					return $this->deleteRecord($id);
				}
			} else {
				if ($method === $httpRequest::METHOD_GET) {
					return $this->getList();
				} else if ($method === $httpRequest::METHOD_POST) {
					$inputData = $request->req('data');
					return $this->postRecord($inputData);
				} else {
					$this->set('errorCause', 'Unsupported method.');
					$response->setStatusCode($response::STATUS_CODE_405);
					return false;
				}
			}
		} catch (Exception\UnsupportedOperation $ex) {
			$response->setContent($ex->getMessage());
			$response->setStatusCode($response::STATUS_CODE_405);
		}

		// We can only get here from the catch block
		return $response;
	}
}
