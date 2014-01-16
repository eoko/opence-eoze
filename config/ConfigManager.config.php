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

/**
 * Configuration of eoze {@link eoko\config\ConfigManager). This configuration file
 * is the first to be read, before the rest of the configuration is loaded. It must
 * return a PHP array because no other reader will be available at this time.
 *
 * Two default configuration locations are hardcoded (more exactly, they are set in
 * eoze default configuration):
 *
 * - %APP%/config
 * - %APP%/config.local
 *
 * If a file named ConfigManager.config.php is found in one of those directories,
 * they are allowed to add more config locations.
 *
 * @since 2012-11-22 23:26
 */
return array(
	'cache' => 'auto',
	'locations' => array(
		'%APP%/config',
		'%APP%/config.local',
	),
);
