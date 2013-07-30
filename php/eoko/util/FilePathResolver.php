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
 * A PathResolver configured for resolving file system paths.
 *
 * @category Eoze
 * @package util
 * @since 2013-02-21 20:12
 */
class FilePathResolver extends PathResolver {

	public function __construct(array $paths = null) {
		$acceptedSeparators = array('/', DIRECTORY_SEPARATOR);
		parent::__construct('/', $acceptedSeparators, $paths);
	}

	/**
	 * Resolves symbolic paths to the matching real path. If a symbolic path does not actually exist in the
	 * file system, it will resolve to `false`.
	 *
	 * @param $spec
	 * @return array|string|bool
	 * @throws \RuntimeException
	 *
	 * @see resolve()
	 */
	public function resolveReal($spec) {
		$result = $this->resolve($spec);

		if (is_array($result)) {
			foreach ($result as $i => &$path) {
				$path = realpath($path);
				if ($path && false !== strstr($this->acceptedSeparators, substr($spec[$i], -1))) {
					$path .= $this->separator;
				}
			}
		} else {
			$result = realpath($result);
			if ($result && false !== strstr($this->acceptedSeparators, substr($spec, -1))) {
				$result .= $this->separator;
			}
		}

		return $result;
	}
}
