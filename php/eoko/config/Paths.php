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

namespace eoko\config;

/**
 * This config object if used to retrieve and store application named paths (tmp, var, etc.).
 *
 * Paths can depends on other path, this way:
 *
 *     // The named path 'cache' will be relative to the named path tmp:
 *     $paths->setPath('cache', ':tmp/cache');
 *
 * Dependencies are automatically resolved, as soon as the dependence value is set, and also
 * when is is modified.
 *
 * @category Eoze
 * @package config
 * @since 2013-02-16 05:43
 */
class Paths extends \eoko\util\FilePathResolver {

}
