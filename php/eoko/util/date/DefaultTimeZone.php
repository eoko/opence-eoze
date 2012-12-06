<?php

namespace eoko\util\date;

use DateTimeZone;

use eoko\config\ConfigManager;

use RuntimeException;

/**
 * Default time zone provider.
 * 
 * @internal Declared abstract to prevent instanciation.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 14 mai 2012
 */
abstract class DefaultTimeZone {

	/**
	 * Default time zone.
	 * @var DateTimeZone
	 */
	private static $defaultTimeZone;

	/**
	 * Gets the default time zone.
	 * @return DateTimeZone
	 * @throws RuntimeException If eoko\util\date\defaultTimeZone is not set.
	 */
	public static function get() {
		if (!self::$defaultTimeZone) {

			$configTimeZone = ConfigManager::get(__NAMESPACE__, 'defaultTimeZone');

			if ($configTimeZone === null) {
				throw new RuntimeException('Missing configuration: ' . __NAMESPACE__ 
						. '/defaultTimeZone');
			} else {
				self::$defaultTimeZone = new DateTimeZone($configTimeZone);
			}
		}
		return self::$defaultTimeZone;
	}
}
