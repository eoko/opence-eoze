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

namespace eoko\cqlix\VirtualField;

/**
 * Factory that creates virtual fields from string spec.
 *
 * @since 2013-06-10 10:52
 */
class SpecFactory {

	/**
	 * @var SpecFactory
	 */
	private static $instance;

	private $providers = array(
		'eoko\cqlix\VirtualField\RelationCount',
	);

	/**
	 * @return SpecFactory
	 */
	public static function getDefault() {
		if (!self::$instance) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * Creates a virtual field for the given spec. If no registered provider accept the spec, then this
	 * method returns NULL.
	 *
	 * @param \ModelTable $table
	 * @param string $spec
	 * @param string|null $name
	 * @return \VirtualField|null
	 */
	public function create(\ModelTable $table, $spec, $name = null) {

		if ($name === null && preg_match('/^(?<name>\w+)\s*(?:=>|:)\s*(?<spec>.+)$/', $spec, $matches)) {
			$name = $matches['name'];
			$spec = $matches['spec'];
		}

		foreach ($this->providers as $class) {
			if (null !== $virtual = $class::fromString($table, $spec, $name)) {
				return $virtual;
			}
		}
	}
}
