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

/**
 * @var string $tableName
 * @var string $dbTableName
 * @var string $modelName
 *
 * @method static ModelTableProxy get()
 *
 * @since 2013-05-16 14:31 (Extracted from ModelTable.php)
 */
abstract class ModelTableProxy {

	/**
	 * @return ModelTable
	 */
	public abstract static function getInstance();

	public abstract function attach(&$pointer);

	public abstract static function getTableName();

	public abstract static function getDBTableName();

	public abstract static function getModelName();

	/**
	 * @return string
	 */
	public static function getModelClass() {
		return static::getModelName();
	}

	/**
	 * @param string|ModelTableProxy $table
	 * @throws InvalidArgumentException
	 * @return ModelTableProxy
	 */
	public static function getFor($table) {
		if ($table instanceof ModelTableProxy) {
			return $table;
		} else if (is_string($table)) {
			$proxyClass = $table;
			if (substr($proxyClass, -5) !== 'Proxy') {
				if (substr($proxyClass, -5) !== 'Table') {
					$proxyClass .= 'Table';
				}
				$proxyClass .= 'Proxy';
			}
			/** @var $proxyClass ModelTableProxy */
			return $proxyClass::get();
		} else {
			throw new InvalidArgumentException();
		}
	}
}
