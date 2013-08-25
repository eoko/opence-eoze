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

namespace eoko\modules\CqlixHistory;

use ModelRelationInfo;
use ModelTable;
use eoko\modules\CqlixHistory\DeltaParser\Factory as DeltaParserFactory;
use eoko\modules\CqlixHistory\DeltaParser\Stack as ParserStack;

/**
 * Context holder for the configuration and objects involved in the history management
 * of one {@link ModelTable Cqlix table}.
 *
 * @category Eoze
 * @package CqlixHistory
 * @since 2013-04-03 14:07
 */
class ModelPluginContext {

	/**
	 * @var ModelTable
	 */
	private $table;

	/**
	 * @var ModelPluginConfiguration
	 */
	private $config;

	/**
	 * @var ParserStack
	 */
	private $parserStack;

	/**
	 * Creates a new ModelPluginContext.
	 *
	 * @param ModelTable $table
	 * @param $config
	 */
	public function __construct(ModelTable $table, $config) {
		$this->table = $table;
		$this->config = ModelPluginConfiguration::parseConfig($config);
	}

	/**
	 * Gets the configured stack of {@link DeltaParser}s.
	 *
	 * @return ParserStack
	 */
	public function getDeltaParserStack() {
		if (!$this->parserStack) {
			$parsers = $this->createDeltaParsers();
			$this->parserStack = new ParserStack($parsers);
		}
		return $this->parserStack;
	}

	/**
	 * Creates the DeltaParser from the configuration.
	 *
	 * @return DeltaParser[]
	 * @throws Exception\Domain
	 */
	protected function createDeltaParsers() {

		$factory = CqlixHistory::getInstance()->getDeltaParserFactory();

		$parsers = array();

		foreach ($this->config->getDeltaParsers() as $name => $config) {

			if ($config === true) {
				$config = array();
			}

			// name shortcut
			if (is_string($config)) {
				$config = array(
					'name' => $config,
				);
			}

			// name shortcut
			if (is_string($name)) {
				// type shortcut
				if (is_string($config)) {
					$config['type'] = $config;
				}
				// name
				if (isset($config['name'])) {
					throw new Exception\Domain(
						'Parser field name cannot be specified both in config and index.'
					);
				}
				$config['name'] = $name;
			}

			$parsers[] = $factory->create($config, $this->table);
		}

		// Default parsers, at the end of the stack
		if ($this->config->getUseDefaultParsers()) {
			$parsers = array_merge($parsers, $factory->createDefaultParsers($this->table));
		}

		return $parsers;
	}
}
