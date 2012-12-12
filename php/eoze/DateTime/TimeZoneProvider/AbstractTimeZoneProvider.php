<?php

namespace eoze\DateTime\TimeZoneProvider;

use eoze\DateTime\TimeZoneProvider;

use DateTimeZone;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 1 nov. 2011
 */
abstract class AbstractTimeZoneProvider implements TimeZoneProvider {

	private static $utcTimeZone;

	public function getUtcName() {
		return 'UTC';
	}

	public function getUtc() {
		if (!self::$utcTimeZone) {
			self::$utcTimeZone = new DateTimeZone($this->getUtcName());
		}
		return self::$utcTimeZone;
	}

}
