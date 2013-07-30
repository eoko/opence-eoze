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

namespace eoko\modules\EozeExt4\controller\Rest\Cqlix\Query\Processor;
use eoko\modules\EozeExt4\controller\Rest\Cqlix\Query\Processor;
use eoko\modules\EozeExt4\controller\Rest\Cqlix\Request\Params as RequestParams;

/**
 * A processor that contains multiple other processors, proxies method calls to them, and combine their
 * result.
 *
 * @since 2013-05-27 10:14
 */
class Stack extends AbstractProcessor {

	/**
	 * @var Processor[]
	 */
	private $stack = array();

	/**
	 * Applies this processor to the passed query.
	 *
	 * @param \ModelTableQuery $query
	 */
	public function apply(\ModelTableQuery $query) {
		foreach ($this->stack as $processor) {
			$processor->apply($query);
		}
	}

	/**
	 * Gets the representation of this processor that can be put in the response data.
	 *
	 * @param RequestParams $requestParams
	 * @return array
	 */
	public function getResponseMetaData(RequestParams $requestParams) {
		$data = array();

		foreach ($this->stack as $processor) {
			$processorData = $processor->getResponseMetaData($requestParams);
			$data = array_merge($data, $processorData);
		}

		return $data;
	}
}
