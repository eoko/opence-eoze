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

use eoko\modules\EozeExt4\controller\Rest\Cqlix\Exception;
use eoko\modules\EozeExt4\Exception\InvalidArgument;
use eoko\modules\EozeExt4\controller\Rest\Cqlix\Request\Params as RequestParams;

/**
 * Query processor for sort param.
 *
 * @since 2013-05-17 15:19
 */
class Sort extends AbstractProcessor {

	/**
	 * Configuration data.
	 *
	 * @var array[]
	 */
	private $sorters = array();

	/**
	 * @inheritdoc
	 */
	protected function setData(array $sorters) {
		foreach ($sorters as $item) {
			if (is_array($item)) {
				if (isset($item['property']) && isset($item['direction'])) {
					$sorter = array(
						'property' => $item['property'],
						'direction' => $item['direction'],
					);
				} else {
					throw new InvalidArgument;
				}
			} else if (is_object($item)) {
				$sorter = array(
					'property' => $item->property,
					'direction' => $item->direction,
				);
			} else {
				throw new InvalidArgument;
			}

			$this->sorters[] = $sorter;
		}
	}

	/**
	 * @inheritdoc
	 */
	public function apply(\ModelTableQuery $query) {
		foreach ($this->sorters as $item) {
			/** @var $property */
			/** @var $direction */
			extract($item);

			$field = $this->resolveFieldName($property);

			if ($field !== null) {
				$query->thenOrderBy($field, $direction);
			} else {
				throw new Exception\UnknownSortField($field);
			}
		}
	}

	/**
	 * @inheritdoc
	 */
	public function getResponseMetaData(RequestParams $requestParams) {
		return array(
			$requestParams->getParamName($requestParams::SORT) => $this->sorters,
		);
	}
}
