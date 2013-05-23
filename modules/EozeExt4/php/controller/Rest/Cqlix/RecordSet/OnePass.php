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

namespace eoko\modules\EozeExt4\controller\Rest\Cqlix\RecordSet;

use eoko\modules\EozeExt4\controller\Rest\Cqlix\Query\Processor as QueryProcessor;
use eoko\modules\EozeExt4\controller\Rest\Cqlix\Request\Params as RequestParams;
use Query;
use eoko\modules\EozeExt4\controller\Rest\Cqlix\DataProxy;
use eoko\modules\EozeExt4\controller\Rest\Cqlix\RecordSet as RecordSetInterface;
use OnePassModelSet;
use Model;
use eoko\modules\EozeExt4\controller\Rest\Cqlix\Record;
use eoko\modules\EozeExt4\controller\Rest\Cqlix\RecordSet\RecordParser;
use eoko\modules\EozeExt4\controller\Rest\Cqlix\RecordSet;

/**
 * A {@link ModelSet} that produces {@link Record} object instead of {@link Model} objects.
 *
 * This model set uses a {@link RecordParser} to creates records from the data read from the query.
 *
 * It also keeps references to the {@link QueryProcessor query processors} that have been applied
 * to create the result data. They are used to construct the meta data of the response (i.e. the
 * information on the request).
 *
 * @since 2013-04-30 15:30
 */
class OnePass extends OnePassModelSet implements RecordSetInterface {

	/**
	 * @var RecordParser
	 */
	private $parser;

	/**
	 * @var QueryProcessor[]
	 */
	private $queryProcessors;

	/**
	 * Creates a new OnePass record set object.
	 *
	 * @param RecordParser $parser
	 * @param Query $query
	 * @param QueryProcessor[] $processors
	 */
	public function __construct(RecordParser $parser, Query $query, array $processors = array()) {
		parent::__construct($parser->getTable(), $query);
		$this->parser = $parser;
		$this->queryProcessors = $processors;
	}

	/**
	 * @inheritdoc
	 */
	protected function createRecord($data) {
		return $this->parser->parseRecord($data);
	}

	/**
	 * @inheritdoc
	 */
	public function getResponseMetaData(RequestParams $requestParams) {

		// Total
		$data = array(
			$requestParams->getParamName($requestParams::TOTAL) => $this->count(),
		);

		// Processors
		foreach ($this->queryProcessors as $processor) {
			foreach ($processor->getResponseMetaData($requestParams) as $name => $value) {
				$data[$name] = $value;
			}
		}

		return $data;
	}
}
