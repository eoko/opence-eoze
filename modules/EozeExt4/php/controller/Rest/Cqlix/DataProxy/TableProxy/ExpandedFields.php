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

namespace eoko\modules\EozeExt4\controller\Rest\Cqlix\DataProxy\TableProxy;

use eoko\modules\EozeExt4\Exception\InvalidArgument as InvalidArgumentException;

/**
 * This class helps data proxies to manage the expanded fields.
 *
 * It determines which fields are expanded and which are not, depending on the fields configuration
 * and the default expand mode.
 *
 * It also centralized expanded fields related data.
 *
 * @since 2013-05-22 14:10
 */
class ExpandedFields {

	/**
	 * Configuration key for default expand mode.
	 *
	 * Non-expandable fields are always expanded by default.
	 *
	 * Expandable fields with `defaultExpand` set to `true` are expanded by default when the default
	 * expand mode is `true`.
	 *
	 * Expandable fields with `defaultExpand` set to `false` are never expanded by default.
	 */
	const CFG_DEFAULT_EXPAND = 'defaultExpand';
	/**
	 * Configuration key for expandable fields.
	 */
	const CFG_EXPANDABLE = 'expandable';

	/**
	 * Default expand mode.
	 *
	 * `false` means that only non-expandable fields will be expanded by default, while `true` means
	 * that expandable fields with `defaultExpand` set to true will also be expanded by default.
	 *
	 * Expandable fields with `defaultExpand` set to false are never expanded by default.
	 *
	 * @var bool
	 */
	private $expandDefault;

	/**
	 * Client names of all the local fields that are expanded, including non-expandable fields.
	 *
	 * @var string[]
	 */
	private $expandedClientFields;
	/**
	 * Client names of the local fields that can be expanded.
	 *
	 * @var string[]
	 */
	private $expandableFields;
	/**
	 * Client names of the local fields that are expanded.
	 *
	 * @var string[]
	 */
	private $expandedExpandableFields;

	/**
	 * Complete graph of expanded fields, rooted at the level of this ExpandedFields' proxy.
	 *
	 * This is an array of this form:
	 *
	 *     array(
	 *         'fieldWithExpandedChildren' => array(
	 *             'expandedChildName' => null,
	 *         ),
	 *         'expandedFieldWithNoExpandedChildren' => null,
	 *     )
	 *
	 * @var array
	 */
	private $expandedFieldsGraph = array();

	/**
	 * Creates a new ExpandedFields object.
	 *
	 * @param bool $expandDefault
	 */
	public function __construct($expandDefault) {
		$this->expandDefault = $expandDefault;
	}

	/**
	 * Sets the value of the request expand param.
	 *
	 * Accepted types for the request params are: string, array of string, or empty (i.e. null).
	 *
	 * @param string|string[]|null $expandParam
	 * @return $this
	 */
	public function setExpandParam($expandParam) {
		$dottedFieldNames = self::parseExpandParamArray($expandParam);
		$this->expandedFieldsGraph = self::expandFields($dottedFieldNames);
		return $this;
	}

	/**
	 * Explodes an array of dotted string of the form:
	 *
	 *     array(
	 *         'my.field.name',
	 *         // ...
	 *     )
	 *
	 * To nested arrays of the form:
	 *
	 *     array(
	 *         'my' => array(
	 *             'field' => array(
	 *                 'name' => null,
	 *             ),
	 *         ),
	 *         // ...
	 *     )
	 *
	 * @param string[] $fields
	 * @return array
	 */
	private static function expandFields($fields) {
		$result = array();
		foreach ($fields as $name) {
			$node =& $result;
			$parts = explode('.', $name);
			foreach ($parts as $part) {
				if (!isset($node[$part])) {
					$node[$part] = null;
				}
				$node =& $node[$part];
			}
		}
		return $result;
	}

	/**
	 * Sets the associated fields config. Calling this method will also trigger the computation
	 * of all result variables of this class.
	 *
	 * Expects an associative array of the form:
	 *
	 *     array(
	 *         // string => array
	 *         $fieldClientName => $fieldConfig,
	 *     )
	 *
	 * @param array $fieldsConfig
	 */
	private function setFieldsConfig(array $fieldsConfig) {

		$expandDefault = $this->expandDefault;

		$expandableFields = array();
		$expandedFields = array();
		$expandedExpandableFields = array();

		foreach ($fieldsConfig as $name => $config) {
			if (empty($config[self::CFG_EXPANDABLE])) {
				$expandedFields[] = $name;
			} else {
				$expandableFields[] = $name;

				if ($expandDefault && $config[self::CFG_DEFAULT_EXPAND]) {
					$expandedFields[] = $name;
					$expandedExpandableFields[] = $name;
				} else if (array_key_exists($name, $this->expandedFieldsGraph)) {
					$expandedFields[] = $name;
					$expandedExpandableFields[] = $name;
				}
			}
		}

		$this->expandedClientFields = $expandedFields;
		$this->expandableFields = $expandableFields;
		$this->expandedExpandableFields = $expandedExpandableFields;
	}

	/**
	 * Converts any accepted type of request param (string, array or null) to
	 * an array of strings.
	 *
	 * @param string|array|null $expandParam
	 * @return string[]
	 * @throws InvalidArgumentException
	 */
	private static function parseExpandParamArray($expandParam) {
		if (empty($expandParam)) {
			return array();
		} else if (is_array($expandParam)) {
			return $expandParam;
		} else if (is_string($expandParam)) {
			return explode(',', $expandParam);
		} else {
			throw new InvalidArgumentException;
		}
	}

	/**
	 * Gets the {@link ExpandedFields} object for the child field specified by its client name. The
	 * returned ExpandedFields will have to be initialized with client fields config by calling the
	 * {@link ExpandedFields::getExpandedClientFields()} method.
	 *
	 * @param string $clientFieldName
	 * @return ExpandedFields
	 */
	public function getChildExpandedFields($clientFieldName) {
		// expand mode does not propagate to children
		$child = new ExpandedFields(false);

		$child->expandedFieldsGraph = isset($this->expandedFieldsGraph[$clientFieldName])
			? $this->expandedFieldsGraph[$clientFieldName]
			: array();

		return $child;
	}

	/**
	 * Gets the name of the local fields that can be expanded.
	 *
	 * @return mixed
	 */
	public function getResponseExpandable() {
		return $this->expandableFields;
	}

	/**
	 * Gets the names of the local fields that are expanded. This list only includes explicitly
	 * or implicitly expanded fields (i.e. it won't include non-expandable fields).
	 *
	 * @return string[]
	 */
	public function getResponseExpanded() {
		return $this->expandedExpandableFields;
	}

	/**
	 * Gets the names of the local fields that are expanded. This list includes non-expandable
	 * fields, as well as explicitly and implicitly expanded fields.
	 *
	 * @param array $fieldsConfig
	 * @return string[]
	 */
	public function getExpandedClientFields(array $fieldsConfig) {
		$this->setFieldsConfig($fieldsConfig);
		return $this->expandedClientFields;
	}
}
