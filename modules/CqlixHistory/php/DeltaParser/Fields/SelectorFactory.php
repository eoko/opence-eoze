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

namespace eoko\modules\CqlixHistory\DeltaParser\Fields;

use eoko\modules\CqlixHistory\Exception;

/**
 * Field selector factory.
 *
 * @category Eoze
 * @package CqlixHistory
 * @subpackage DeltaParser
 * @since 2013-04-03 11:51
 */
class SelectorFactory {

	/**
	 * @var string
	 */
	private static $defaultClass = 'eoko\modules\CqlixHistory\DeltaParser\Fields\Selector\DefaultColumns';

	/**
	 * Creates a new selector from the passed config.
	 *
	 * The argument can be any of:
	 *
	 * - A `Selector` object, that will be returned as is.
	 * - A config array.
	 * - A string specifying the type or class name of the selector to create (with default
	 *   options.
	 *
	 * @param array|Selector|string $selector
	 * @return Selector
	 */
	public static function create($selector) {
		if ($selector instanceof Selector) {
			return $selector;
		} else if (is_string($selector)) {
			$class = self::getSelectorClass($selector);
			return new $class;
		} else if (isset($selector['class'])) {
			$class = $selector['class'];
			return new $class($selector);
		} else if (isset($selector['type'])) {
			$class = self::getSelectorClass($selector['type']);
			return new $class($selector);
		} else {
			$class = self::$defaultClass;
			return new $class($selector);
		}
	}

	/**
	 * Get the class for the specified selector type.
	 *
	 * @param string $type
	 * @return string
	 * @throws Exception\Domain
	 */
	private static function getSelectorClass($type) {
		if (class_exists($type)) {
			return $type;
		} else {
			$class = __NAMESPACE__ . '\Selector\\' . $type;
			if (class_exists($type)) {
				return $class;
			}
		}
		// Fail
		throw new Exception\Domain('Cannot find selector class for type: ' . $type . '.');
	}

	/**
	 * Creates a new selector of the configured default type, with the specified config.
	 *
	 * @param array $config
	 * @return Selector
	 */
	public static function createDefault(array $config = null) {
		return new self::$defaultClass($config);
	}
}
