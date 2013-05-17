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

namespace eoko\context;
use Traversable;
use Zend\Stdlib\ArrayUtils;

/**
 * @since 2013-05-17 12:04 (Extracted from Router.php)
 */
class Router_RouteConfigAssembler {

	private $routes;

	private $childRoutes;

	public function addRoutes($routes) {
		foreach ($routes as $name => $route) {
			if ($route instanceof Traversable) {
				$route = ArrayUtils::iteratorToArray($route);
			}
			if (is_array($route)) {
				// Extract children routes
				if (isset($route['parent_segment'])) {
					// Trim parent segment name from route name beginning
					$parentSegment = $route['parent_segment'];
					if (strpos($name, $parentSegment . '/') === 0) {
						$name = substr($name, strlen($parentSegment) + 1);
					}
					// Remove eoze custom parent_segment option
					unset($route['parent_segment']);
					// Store
					$this->childRoutes[$parentSegment][$name] = $route;
				} else {
					$this->routes[$name] = $route;
				}
			} else {
				$this->routes[$name] = $route;
			}
		}
	}

	/**
	 * Construct an array of references to route configs that have a
	 * 'child_routes' key (that is, parent routes), indexed with their
	 * fully qualified names.
	 * @param array $routes
	 * @param string $prefix
	 * @return array
	 */
	private static function mapParentRoutes(&$routes, $prefix = null) {
		$map = array();
		if (!$routes) {
			return $map;
		}
		foreach ($routes as $name => &$route) {
			if ($route instanceof Traversable) {
				$route = ArrayUtils::iteratorToArray($route);
			}
			if (is_array($route)) {
				if (isset($route['child_routes'])) {
					$fqRouteName = $prefix . $name;
					$map[$fqRouteName] =& $route;
					$map += self::mapParentRoutes($route['child_routes'], $fqRouteName . '/');
				}
			}
		}
		return $map;
	}

	public function assembleRoutes() {
		if ($this->childRoutes) {
			// Build parent name map
			$map = self::mapParentRoutes($this->routes);
			foreach ($this->childRoutes as &$routes) {
				$map += self::mapParentRoutes($routes);
			}
			unset($routes);

			// Assemble
			foreach ($this->childRoutes as $parent => $children) {
				foreach ($children as $name => $route) {
					if (isset($map[$parent])) {
						$map[$parent]['child_routes'][$name] = $route;
					} else {
						throw new RuntimeException(
							"Invalid 'parent_segment' value: cannot find a parent "
							. "route named $parent."
						);
					}
				}
			}

			// prevent reprocessing if the method is called again
			unset($this->childRoutes);
		}

		return $this->routes;
	}
}
