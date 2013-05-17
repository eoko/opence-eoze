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

namespace eoko\mvc;
use Zend\Http\PhpEnvironment\Response;

/**
 * A router that send an error response.
 *
 * @since 2013-05-17 11:52
 */
class ErrorRouter extends LegacyRouter {

	/**
	 * @var int
	 */
	private $errorCode;

	/**
	 * @var null|string
	 */
	private $responseContent;

	/**
	 * Creates a new ErrorRouter.
	 *
	 * @param int $errorCode
	 * @param string|null $responseContent
	 */
	public function __construct($errorCode, $responseContent = null) {
		$this->errorCode = $errorCode;
		$this->responseContent = $responseContent;
	}

	/**
	 * @inheritdoc
	 */
	protected function getResponse() {
		$response = new Response();
		$response->setStatusCode($this->errorCode);
		if (isset($this->responseContent)) {
			$response->setContent($this->responseContent);
		}
		return $response;
	}
}
