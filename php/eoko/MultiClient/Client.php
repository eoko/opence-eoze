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

namespace eoko\MultiClient;

/**
 * Client data object for multiple clients installations.
 *
 * @category Eoze
 * @package MultiClient
 * @since 2013-02-16 04:44
 */
class Client {

	/**
	 * @var string
	 */
	private $name;
	/**
	 * @var string
	 */
	private $homeDirectory;
	/**
	 * @var string
	 */
	private $databaseName;

	public function __construct(array $config) {
		foreach ($config as $key => $value) {
			$this->$key = $value;
		}
	}

	/**
	 * @return string
	 */
	public function getDatabaseName() {
		return $this->databaseName;
	}

	/**
	 * @return string
	 */
	public function getHomeDirectory() {
		return $this->homeDirectory;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

}
