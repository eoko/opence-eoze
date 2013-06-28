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
use eoko\util\Arrays;

/**
 * Abstract scaffolding for a RESTFul executor.
 *
 * @since 2013-04-18 10:26
 */
abstract class Rest extends JsonExecutor {

	const OPERATION_CREATE = 'create';
	const OPERATION_READ = 'read';
	const OPERATION_UPDATE = 'update';
	const OPERATION_DELETE = 'delete';

	/**
	 * CRUD operation for this request. The value is one of the constants of this class.
	 *
	 * @see Rest::OPERATION_CREATE
	 * @see Rest::OPERATION_READ
	 * @see Rest::OPERATION_UPDATE
	 * @see Rest::OPERATION_DELETE
	 *
	 * @var string
	 */
	private $crudOperation;

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

	// TODO doc
	abstract public function deleteRecords($data);

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

	// TODO doc
	abstract public function putRecords($data);

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
	 * Gets the current CRUD operation.
	 *
	 * @return string
	 */
	protected function getCrudOperation() {
		return $this->crudOperation;
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

			// The request may not need to be processed if the data store has not been modified
			if ($method === $httpRequest::METHOD_GET) {

				$lastModified = $this->getLastModified();

				if ($lastModified) {

					$response->getHeaders()->addHeaders(array(
						'Cache-Control' => 'private, max-age=604800',
						'Last-Modified' => $lastModified->format('r'),
					));

					header_remove('Pragma');

					/** @var \Zend\Http\Header\IfModifiedSince $ifModifiedSinceHeader */
					$ifModifiedSinceHeader = $request->getHttpRequest()->getHeader('If-Modified-Since');
					if ($ifModifiedSinceHeader) {
						if (!$lastModified->diff($ifModifiedSinceHeader->date())->invert) {
							$response->setStatusCode($response::STATUS_CODE_304);
							return $response;
						}
					}
				} else {
					$response->getHeaders()->addHeaders(array(
						'Cache-Control' => 'private, no-cache',
					));
				}
			}

			if ($id) {
				if ($method === $httpRequest::METHOD_GET) {
					$this->crudOperation = self::OPERATION_READ;

					$response->getHeaders()->addHeaders(array(
						'Cache-Control' => 'private, no-cache',
					));

					return $this->getRecord($id);
				} else if ($method === $httpRequest::METHOD_PUT || $method === $httpRequest::METHOD_POST) {
					$this->crudOperation = self::OPERATION_UPDATE;
					$inputData = $request->req('data');
					return $this->putRecord($id, $inputData);
				} else if ($method === $httpRequest::METHOD_DELETE) {
					$this->crudOperation = self::OPERATION_DELETE;
					if (is_array($id)) {
						return $this->deleteRecords($id);
					} else {
						return $this->deleteRecord($id);
					}
				}
			} else {
				if ($method === $httpRequest::METHOD_GET) {
					$this->crudOperation = self::OPERATION_READ;
					return $this->getList();
				} else if ($method === $httpRequest::METHOD_POST) {
					$this->crudOperation = self::OPERATION_CREATE;
					$inputData = $request->req('data');
					return $this->postRecord($inputData);
				} else if ($method === $httpRequest::METHOD_PUT) {
					$this->crudOperation = self::OPERATION_UPDATE;
					$data = $request->req('data');
					if (Arrays::isAssoc($data)) {
						$data = array($data);
					}
					return $this->putRecords($data);
				} else if ($method === $httpRequest::METHOD_DELETE) {
					$this->crudOperation = self::OPERATION_DELETE;
					$data = $request->req('data');
					if (Arrays::isAssoc($data)) {
						$data = array($data);
					}
					return $this->deleteRecords($data);
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

	/**
	 * @return \DateTime
	 */
	protected function getLastModified() {
		return null;
	}
}
