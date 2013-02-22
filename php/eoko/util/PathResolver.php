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
 * Store and resolve symbolic paths.
 *
 * @category Eoze
 * @package util
 * @since 2013-02-21 20:01
 */
class PathResolver {

	/**
	 * @var string
	 */
	protected $separator;
	/**
	 * Escaped separator to use in regex.
	 * @var string
	 */
	protected $reSeparator;
	/**
	 * @var string
	 */
	protected $acceptedSeparators;

	private $pathRoots = array();
	private $dependentSpecs = array();
	private $previousDependencyRoots = array();

	public function __construct($separator, $acceptedSeparators, array $paths = null) {
		// configure
		$this->separator = $separator;
		$this->reSeparator = preg_quote($this->separator, '|');
		$this->acceptedSeparators = $acceptedSeparators;
		if (is_array($this->acceptedSeparators)) {
			$this->acceptedSeparators = implode('', $this->acceptedSeparators);
		}

		// register constructor paths
		if ($paths) {
			$this->setPaths($paths);
		}

		// init
		$this->init(null);
	}

	protected function init() {}

	/**
	 * Set the base path for the given path alias.
	 *
	 * @param $name
	 * @param $path
	 */
	public function setPath($name, $path) {

		$path = rtrim($path, $this->acceptedSeparators) . $this->separator;

		$res = $this->reSeparator;
		$regex = '|^:(?<root>[^' . $res . ']+)(?:' . $res . '(?<path>.*))?$|';

		if (preg_match($regex, $path, $matches)) {
			$this->dependentSpecs[$matches['root']][$name] = $matches['path'];
			$this->previousDependencyRoots[$name] = $matches['root'];

			if (isset($this->pathRoots[$matches['root']])) {
				$this->setPathValue($name, $this->pathRoots[$matches['root']] . $matches['path']);
			}
		} else {
			if (isset($this->previousDependencyRoots[$name])) {
				unset($this->dependentSpecs[$this->previousDependencyRoots[$name]][$name]);
			}

			$this->setPathValue($name, $path);
		}
	}

	/**
	 * Sets the path value, and resolve existing dependencies. This method does not affect the stored
	 * specs for the specified name.
	 *
	 * @param $name
	 * @param $path
	 */
	private function setPathValue($name, $path) {
		$this->pathRoots[$name] = $path;

		if (!empty($this->dependentSpecs[$name])) {
			foreach ($this->dependentSpecs[$name] as $child => $childPath) {
				$this->setPathValue($child, $path . $childPath);
			}
		}
	}

	/**
	 * Sets multiple paths in one call.
	 *
	 * @param array $paths
	 */
	public function setPaths(array $paths) {
		foreach ($paths as $name => $path) {
			$this->setPath($name, $path);
		}
	}

	/**
	 * Resolves a symbolic path to its file system path. The first node of the specified path spec
	 * will be used as the alias of the base directory.
	 *
	 * For example, `'tmp/myDir/myFile'` will return the path for the file `myDir/myFile` in the
	 * symbolic directory `tmp`.
	 *
	 * @param string|string[] $path
	 * @return string|string[]
	 * @throws \RuntimeException
	 */
	public function resolve($path) {
		// Array form
		if (is_array($path)) {
			$paths = array();
			foreach ($path as $pathItem) {
				$paths[] = $this->resolve($pathItem);
			}
			return $paths;
		}

		if (isset($this->pathRoots[$path])) {
			return $this->pathRoots[$path];
		} else {
			$res = $this->reSeparator;
			$regex = '|^:?(?<root>[^' . $res . ']+)(?:' . $res . '(?<path>.*))?$|';
			if (preg_match($regex, $path, $matches)) {
				if (isset($this->pathRoots[$matches['root']])) {
					return $this->pathRoots[$matches['root']] . $matches['path'];
				} else {
					throw new \RuntimeException('Cannot resolve path: ' . $path);
				}
			} else {
				throw new \RuntimeException('Invalid path: ' . $path);
			}
		}
	}

	public function resolveAs(array $aliasedPaths) {
		$result = array();
		dump($aliasedPaths);
		foreach ($aliasedPaths as $alias => $path) {
				$result[$alias] = $this->resolve($path);
			}
		return $result;
	}
}
