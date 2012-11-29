<?php

namespace eoze\DateTime\TimeZoneProvider;

use DateTimeZone;

use eoze\util\PageInfo;
/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 1 nov. 2011
 */
class PhpTimeZoneProvider extends AbstractTimeZoneProvider {
	
	public function getTimeZone($name) {
		return new DateTimeZone($name);
	}

	public function listAbbreviations(PageInfo $page = null) {
		return PageInfo::getArrayPage(DateTimeZone::listAbbreviations(), $page);
	}

	public function listIdentifiers($what = DateTimeZone::ALL, $country = null, PageInfo $page = null) {
		return PageInfo::getArrayPage(DateTimeZone::listIdentifiers($what, $country), $page);
	}
	
}
