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

namespace eoko\modules\EozeExt4\controller\Rest\Cqlix;

use eoko\modules\EozeExt4\controller\Rest\Cqlix\Query\FieldNameResolver;
use eoko\modules\EozeExt4\controller\Rest\Cqlix\Request\Params as RequestParams;
use eoko\modules\EozeExt4\controller\Rest\Cqlix\RecordSet\RecordParser;
use eoko\modules\EozeExt4\controller\Rest\Cqlix\DataProxy\TableProxy\ExpandedFields;
use Model;
use Query;
use eoko\modules\EozeExt4\Exception;

/**
 * Proxy used by this module's controllers to read and write to Cqlix records.
 *
 * Data proxies holds the configuration and implements the procedures needed to abstract server data
 * model from client.
 *
 * Data proxies can be nested, that is a proxy can use another proxy to convert between client and
 * server config.
 *
 * @since 2013-04-18 10:34
 */
interface DataProxy extends FieldNameResolver {

	/**
	 * Get the model table associated to this proxy.
	 *
	 * @return \ModelTable
	 */
	public function getTable();

	/**
	 * Sets the expand param to be used by this proxy. This is only used for root proxies; for child
	 * ones use {@link DataProxy::setExpandFields()}.
	 *
	 * The `$expandParam` is a list of client fields to expand, either in the form of a comma separated
	 * string (with no spaces), or an array of strings.
	 *
	 * @param string|string[] $expandParam
	 * @param bool $expandDefault
	 * @return DataProxy $this
	 */
	public function setExpandedParams($expandParam, $expandDefault);

	/**
	 * Sets the {@link ExpandedFields} to be used by this proxy.
	 *
	 * @param ExpandedFields $expandedFields
	 * @return DataProxy $this
	 */
	public function setExpandedFields(ExpandedFields $expandedFields);

	/**
	 * Gets the client names of the fields that can be expanded. This list will contains
	 * the name in dotted notation of the expandable fields of fields that are expanded.
	 *
	 * @return string[]
	 */
	public function getResponseExpandable();

	/**
	 * Gets the client names of the fields that are expanded, including the name in dotted
	 * notation of expanded fields of expanded records.
	 *
	 * @return string[]
	 */
	public function getResponseExpanded();

	/**
	 * Creates a record with the specified data.
	 *
	 * @param array $data
	 * @param RequestParams $requestParams
	 * @return Model
	 */
	public function createRecord(array $data = null, RequestParams $requestParams);

	/**
	 * Loads the specified record. If the record does not exist, this method will return `null`.
	 *
	 * @param mixed $id
	 * @param RequestParams $requestParams
	 * @return Model|null
	 */
	public function loadRecord($id, RequestParams $requestParams);

	/**
	 * Gets the formatted data for the given record.
	 *
	 * @param Record $record
	 * @return array
	 */
	public function getRecordData(Record $record);

	/**
	 * Gets the primary key value of the record specified with the passed data.
	 *
	 * @param array $data
	 * @param bool $require
	 * @throws Exception\IllegalState If the proxy cannot resolve the server-side name of the id field
	 * @throws Exception\InvalidArgument If the passed data doesn't contain record id, and require is true
	 * @return mixed
	 */
	public function getIdFromData(array $data, $require = true);

	/**
	 * Updates the passed record with the given data.
	 *
	 * @param Model $record
	 * @param array $data
	 */
	public function setRecordData(Model $record, array $data);

	/**
	 * Creates the record set for the supplied request params.
	 *
	 * @param RequestParams $requestParams
	 * @return RecordSet
	 */
	public function createRequestRecords(RequestParams $requestParams);

	/**
	 * Adds SELECT clauses to the passed query to retrieve the data needed by this proxy.
	 *
	 * The `$fieldPrefix` parameter is used for selecting fields from child proxies.
	 *
	 * The method returns a {@link RecordParser} that should be used to configure the
	 * {@link RecordSet} that will be created, or can be embedded in another `RecordParser`,
	 * if this method is called from a child proxy.
	 *
	 * @param Query $query
	 * @param string $fieldPrefix
	 * @return RecordParser
	 */
	public function selectListFields(Query $query, $fieldPrefix = '');

	/**
	 * Gets the meta data for the record type represented by this proxy. This data is intended
	 * to be used by the client to configure its record class.
	 *
	 * @return array
	 */
	public function getMetaData();
}
