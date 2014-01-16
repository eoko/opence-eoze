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

/**
 * Configuration container for the history plugin.
 *
 *
 * History plugin configuration
 * ----------------------------
 *
 * - `useDefaultParsers` (bool) Default: `false`.
 * - `parsers` (array)
 *
 *
 * Accepted form for parser config
 * -------------------------------
 *
 *     'parsers' => array(
 *         // 1. Plain config
 *         array('class' => $class, ...),
 *
 *         // 2. Field shortcut: string index or plain string value will be used as the
 *         //    'name' config option
 *         'fieldName' => CONFIG,
 *         // or
 *         'fieldName',
 *         // or
 *         'fieldName' => true,
 *
 *         // Shortcuts can also be used for the parser type
 *         'fieldName' => 'My\Parser\Class', // or type
 *
 *     ), // ...
 *
 *
 * Parser config options
 * ---------------------
 *
 * -   `class` (string)
 *     The class of the parser. `type` can be used instead.
 *
 * -   `type` (string)
 *     Will be used to determine the class of the parser from the configured aliases. The
 *     class name of the parser can also be used as its `type`.
 *
 * -   [`name`] (string)
 *     Name of the model field targeted by the parser (only meaningful for parsers processing
 *     a single field).
 *
 * @category Eoze
 * @package CqlixHistory
 * @since 2013-04-02 15:54
 */
class ModelPluginConfiguration {

	/**
	 * Aliases that can be used in configuration arrays, and the associated setter method.
	 *
	 * @var array
	 */
	private static $configAliases = array(
		'parser' => 'setDeltaParsers',
		'parsers' => 'setDeltaParsers',
	);

	/**
	 * @var bool
	 */
	private $useDefaultParsers = false;

	/**
	 * @var array
	 */
	private $deltaParsers = null;

	/**
	 * Creates a new configuration instance.
	 *
	 * @param array $config
	 * @throws Exception\InvalidArgument
	 */
	public function __construct(array $config = null) {
		if ($config) {
			foreach ($config as $key => $value) {
				$method = isset(self::$configAliases[$key])
					? self::$configAliases[$key]
					: 'set' . ucfirst($key);
				if (method_exists($this, $method)) {
					$this->$method($value);
				} else {
					throw new Exception\InvalidArgument(
						'Invalid config key: ' . $key
					);
				}
			}
		}
	}

	/**
	 * Parses a config object from the supplied mixed argument.
	 *
	 * @param array|ModelPluginConfiguration $config
	 * @throws Exception\InvalidArgument
	 * @return ModelPluginConfiguration
	 */
	public static function parseConfig($config) {
		if ($config) {
			if (is_array($config)) {
				return new self($config);
			} else if ($config instanceof self) {
				return $config;
			} else {
				throw new Exception\InvalidArgument(
					'$config must be an array or an instance of ' . __CLASS__
				);
			}
		} else {
			return new self;
		}
	}

	/**
	 * Configure whether the default parsers should be used or not.
	 *
	 * @param bool $useDefaultParsers
	 * @return $this
	 */
	public function setUseDefaultParsers($useDefaultParsers) {
		$this->useDefaultParsers = $useDefaultParsers;
		return $this;
	}

	/**
	 * Gets whether the default parsers should be used or not.
	 *
	 * @return bool
	 */
	public function getUseDefaultParsers() {
		return $this->useDefaultParsers;
	}

	/**
	 * Configure delta parsers.
	 *
	 * The following alias can be used in configuration array: `parsers`.
	 *
	 * @param array $parsers
	 * @return $this
	 */
	public function setDeltaParsers(array $parsers) {
		$this->deltaParsers = $parsers;
		return $this;
	}

	/**
	 * Gets the delta parsers configuration.
	 *
	 * @return array
	 */
	public function getDeltaParsers() {
		return $this->deltaParsers;
	}
}
