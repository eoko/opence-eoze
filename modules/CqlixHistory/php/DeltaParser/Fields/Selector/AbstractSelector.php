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

namespace eoko\modules\CqlixHistory\DeltaParser\Fields\Selector;

use ModelTable;
use eoko\modules\CqlixHistory\DeltaParser\Fields\Selector;
use eoko\modules\CqlixHistory\Exception;

/**
 * Base class for selectors, that implements configuration logic.
 *
 * @category Eoze
 * @package CqlixHistory
 * @subpackage DeltaParser
 * @since 2013-04-04 11:46
 */
abstract class AbstractSelector implements Selector {

	/**
	 * Associative array of configuration aliases, and their associated setter method name.
	 *
	 * @var string[]
	 */
	protected static $configAliases;

	/**
	 * Creates a new Selector instance.
	 *
	 * @param array $config
	 * @throws Exception\InvalidArgument If an invalid configuration key is found.
	 */
	public function __construct(array $config = null) {
		if ($config) {
			foreach ($config as $key => $value) {
				$method = isset(static::$configAliases[$key])
					? static::$configAliases[$key]
					: 'set' . ucfirst($key);
				if (method_exists($this, $method)) {
					$this->$method($value);
				} else {
					throw new Exception\InvalidArgument('Invalid config key: ' . $key);
				}
			}
		}
	}
}
