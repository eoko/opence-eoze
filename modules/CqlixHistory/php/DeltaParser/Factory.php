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

namespace eoko\modules\CqlixHistory\DeltaParser;

use ModelTable;
use eoko\modules\CqlixHistory\Exception;
use eoko\modules\CqlixHistory\DeltaParser;

/**
 * DeltaParser factory.
 *
 * @category Eoze
 * @package CqlixHistory
 * @subpackage DeltaParser
 * @since 2013-04-03 12:07
 */
class Factory {

	/**
	 * @var string[]
	 */
	private $parserAliases = array();

	/**
	 * Extracts the delta parser from the supplied argument, that can be either some sort of config for
	 * creating a new delta parser, or an existing instance of delta parser.
	 *
	 * @param DeltaParser|string|array $parser
	 * @param ModelTable $table The table is used to guess parser config from field name.
	 * @throws Exception\InvalidArgument
	 * @return DeltaParser
	 */
	public function create($parser, ModelTable $table) {
		if ($parser instanceof DeltaParser) {
			return $parser;
		} else if (is_string($parser)) {
			/** @var $class AbstractParser */
			$class = $this->getParserClass($parser);
			return $class::createForTable($table);
		} else if (is_array($parser)) {
			/** @var $class AbstractParser */
			if (isset($parser['class'])) {
				$class = $parser['class'];
			} else if (isset($parser['type'])) {
				$class = $this->getParserClass($parser['type']);
			} else if (isset($parser['name'])) {
				$class = $this->guessParserClass($table, $parser['name']);
			} else {
				throw new Exception\InvalidArgument('Missing field name (required to guess parser class)');
			}
			return $class::createForTable($table, $parser);
		} else {
			throw new Exception\InvalidArgument(
				'Type of $parser must be one of: string, array, or DeltaParser.'
			);
		}
	}

	/**
	 * Gets the parser class for the specified alias.
	 *
	 * @param $alias
	 * @return string
	 * @throws Exception\IllegalState
	 */
	protected function getParserClass($alias) {
		if (isset($this->parserAliases[$alias])) {
			return $this->parserAliases[$alias];
		} else if (class_exists($alias)) {
			return $alias;
		} else {
			$cleanedAlias = str_replace(array('.','/'), '\\', $alias);
			$class = __NAMESPACE__ . '\\' . ucfirst($cleanedAlias);
			if (class_exists($class)) {
				return $class;
			} else {
				throw new Exception\IllegalState('Cannot find parser class for alias: ' . $alias . '.');
			}
		}
	}

	/**
	 * Guesses the parser class for the specified field in the the given table.
	 *
	 * @param ModelTable $table
	 * @param string $fieldName
	 * @return string
	 * @throws \UnsupportedOperationException
	 */
	protected function guessParserClass(ModelTable $table, $fieldName) {
		$field = $table->getField($fieldName, true);

		if ($field instanceof \ModelColumn) {
			return __NAMESPACE__ . '\SingleFieldParser';
		} else if ($field instanceof \ModelRelationInfo) {
			if ($field instanceof \ModelRelationInfoHasOne) {
				return __NAMESPACE__ . '\Relation\HasOne';
			} else {
				return __NAMESPACE__ . '\Relation\HasMany';
			}
		}

		throw new \UnsupportedOperationException();
	}

	/**
	 * Adds an alias that can be used as the `type` configuration for delta parsers.
	 *
	 * @param string $alias
	 * @param string $class
	 * @return Factory $this
	 */
	public function addParserAlias($alias, $class) {
		$this->parserAliases[$alias] = $class;
		return $this;
	}

	/**
	 * Creates the default parser set for the passed table.
	 *
	 * @param ModelTable $table
	 * @return DeltaParser[]
	 */
	public function createDefaultParsers(ModelTable $table) {
		$parsers = array();

		$parsers[] = new DeltaParser\Fields(array(
			'table' => $table,
		));

		return $parsers;
	}
}
