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
use eoko\module\traits\HasCssFiles;
use eoko\module\traits\HasRoutes;

/**
 * Eoze module for Ext JS 4.
 *
 * @since 2012-11-28 15:57
 */
class EozeExt4 extends Module implements HasRoutes, HasCssFiles {

	/**
	 * @inheritdoc
	 *
	 * @internal Overridden to adds loader config for Ext4 ux extensions.
	 */
	public function getExt4LoaderConfig() {
		$paths = parent::getExt4LoaderConfig();

		$paths['Ext.ux'] = 'cdn/ext4ux';

		return $paths;
	}

	/**
	 * Gets the path to this module's ext4 ux source directories.
	 *
	 * @return string[]
	 */
	public function getExt4UxSourcePaths() {
		$paths = array();

		$folder = 'js.ext4-ux';
		foreach ($this->getLocation()->getLineActualLocations(true) as $location) {
			/** @var \eoko\module\ModuleLocation $location  */
			$directory = $location->getDirectory();
			$name = $location->moduleName;
			$path = $directory->path . $name;
			if (is_dir($path . '/' . $folder)) {
				$paths[] = $path . '/' . $folder;
			}
		}

		return $paths;
	}

	/**
	 * @inheritdoc
	 */
	public function getRoutesConfig() {
		$config = $this->getConfig();
		return isset($config['router'])
			? $config['router']
			: null;
	}

	/**
	 * @inheritdoc
	 */
	public function getModuleCssUrls() {
		$urls = parent::getModuleCssUrls();

		$files = $this->getConfig()->get('ux-css', array());
		foreach ($files as $url) {
			if (strstr($url, '://') === false) {
				$url = SITE_BASE_URL . $url;
			}
			$urls[] = $url;
		}

		return $urls;
	}
}
