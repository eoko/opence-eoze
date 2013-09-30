<?php
/**
 * Copyright (C) 2012 Eoko
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
 * @copyright Copyright (C) 2012 Eoko
 * @licence http://www.gnu.org/licenses/gpl.txt GPLv3
 * @author Éric Ortega <eric@eoko.fr>
 */

namespace eoko\modules\AjaxRouter;

use eoko\module\executor\JsonExecutor;
use eoko\module\ModuleManager;

/**
 * Executor providing configuration for AjaxRouter.
 *
 * @category Eoze
 * @package AjaxRouter
 * @subpackage Module
 * @since 2012-12-18 10:27
 */
class Config extends JsonExecutor {

	public function getRoutesConfig() {

		$routes = array();

		foreach (ModuleManager::listModules() as $module) {
			/** @var \eoko\module\Module $module */
			if ($module instanceof HasAjaxRoutes || method_exists($module, 'getAjaxRoutes')) {
				/** @var HasAjaxRoutes $module */
				$moduleRoutes = $module->getAjaxRoutes();
				$routes = array_merge($routes, $module->getAjaxRoutes());
			}
		}

		$this->set('routes', $routes);

		return true;
	}
}
