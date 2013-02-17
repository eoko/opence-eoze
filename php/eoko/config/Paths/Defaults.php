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

namespace eoko\config\Paths;

use eoko\config\Paths;

/**
 * Default paths configuration (child paths are configured relatively to the installation home
 * directory).
 *
 * @category Eoze
 * @package config
 * @subpackage Paths
 * @since 2013-02-16 08:52
 */
class Defaults extends Paths {

	public function init() {
		parent::init();
		$this->setPaths(array(

			'tmp' => ':home/tmp',
			'var' => ':home/var',

			'cache' => ':tmp/cache',
			'log' => ':var/log',

			'media' => ':home/documents',
			// deprecated
			'medias' => ':home/documents',
		));
	}
}
