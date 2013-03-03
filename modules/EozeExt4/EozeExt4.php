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

namespace eoko\modules\EozeExt4;

use eoko\module\Module;

/**
 *
 * @category Eoze
 * @package Ext4
 * @subpackage Module
 * @since 2012-11-28 15:57
 */
class EozeExt4 extends Module {

	public function getExt4LoaderConfig() {
		$paths = parent::getExt4LoaderConfig();

		foreach ($this->getLocation()->getLineActualLocations(true) as $location) {
			/** @var \eoko\module\ModuleLocation $location  */
			$directory = $location->getDirectory();
			$name = $location->moduleName;
			$path = $directory->path . $name;
			$url = $location->url;

			$folder = 'js.ext4-ux';
			if (is_dir($path . '/' . $folder)) {
				$namespace = 'Ext.ux';
				$paths[$namespace] = $url . $folder;
			}
		}

		return $paths;
	}
}
