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

namespace eoko\util;

/**
 * A PathResolver preconfigured for resolving namespaces.
 *
 * @category Eoze
 * @package util
 * @since 2013-02-21 20:09
 */
class NamespaceResolver extends PathResolver {

	public function __construct(array $paths = null) {
		parent::__construct('\\', '\\', $paths);
	}

	/**
	 * Resolve one or more namespace name. The result will have no separator, neither at its beginning,
	 * nor at its end.
	 *
	 * @param $pathOrPaths
	 * @return array|string
	 */
	public function resolveNamespace($pathOrPaths) {
		$result = $this->resolve($pathOrPaths);

		if (is_array($result)) {
			foreach ($result as &$path) {
				$path = trim($path, $this->acceptedSeparators);
			}
		} else {
			$result = trim($result, $this->acceptedSeparators);
		}

		return $result;
	}

	public function resolveNamespaceAs(array $aliasedNames) {
		$result = array();

		foreach ($aliasedNames as $alias => $name) {
			$result[$alias] = $this->resolveNamespace($name);
		}

		return $result;
	}
}
