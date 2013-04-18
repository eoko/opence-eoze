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

use UnsupportedOperationException;
use Zend\Http\PhpEnvironment\Request as HttpRequest;
use Zend\Http\Response;
use eoko\module\executor\JsonExecutor;

/**
 * Scaffolding for a RESTFul executor.
 *
 * @since 2013-04-18 10:26
 */
abstract class Rest extends JsonExecutor {

	/**
	 * Cache for HTTP request object.
	 *
	 * @var HttpRequest
	 */
	private $httpRequest;

	/**
	 * Gets the HTTP request object.
	 *
	 * @return HttpRequest
	 */
	protected function getHttpRequest() {
		if (!$this->httpRequest) {
			$this->httpRequest = new HttpRequest();
		}
		return $this->httpRequest;
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
	 * @throws UnsupportedOperationException
	 * @return bool|Response
	 */
	public function listRecords() {
		throw new UnsupportedOperationException('TODO');
	}

	/**
	 * Action for deleting record(s).
	 *
	 * @param mixed $id
	 * @throws UnsupportedOperationException
	 * @return bool|Response
	 */
	public function deleteRecord($id = null) {
		throw new UnsupportedOperationException('TODO');
	}

	/**
	 * Action for updating existing records.
	 *
	 * @param mixed $id
	 * @param array $inputData
	 * @throws UnsupportedOperationException
	 * @return bool|Response
	 */
	public function postRecord($id = null, $inputData = null) {
		throw new UnsupportedOperationException('TODO');
	}

	/**
	 * Action for reading a record's data.
	 *
	 * @param mixed $id
	 * @throws UnsupportedOperationException
	 * @return bool|Response
	 */
	public function getRecord($id = null) {
		throw new UnsupportedOperationException('TODO');
	}

	/**
	 * Index action.
	 *
	 * @return bool|Response
	 * @throws UnsupportedOperationException
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
				} else if ($method === $httpRequest::METHOD_POST) {
					$inputData = $request->req('data');
					return $this->postRecord($id, $inputData);
				} else if ($method === $httpRequest::METHOD_DELETE) {
					return $this->deleteRecord($id);
				}
			} else {
				if ($method === $httpRequest::METHOD_GET) {
					return $this->listRecords();
				} else {
					throw new UnsupportedOperationException();
				}
			}
		} catch (UnsupportedOperationException $ex) {
			$response->setContent($ex->getMessage());
			$response->setStatusCode($response::STATUS_CODE_500);
			return $response;
		}
	}
}
