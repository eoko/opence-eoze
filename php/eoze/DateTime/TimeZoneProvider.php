<?php

namespace eoze\DateTime;

use DateTimeZone;

use eoze\util\PageInfo;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 1 nov. 2011
 */
interface TimeZoneProvider {
	
	function listAbbreviations(PageInfo $page = null);
	
	function listIdentifiers($what = DateTimeZone::ALL, $country = null, PageInfo $page = null);
	
	/**
	 * @return DateTimeZone
	 */
	function getTimeZone($name);
	
	function getUtcName();
	
	/**
	 * @return DateTimeZone
	 */
	function getUtc();
	
}
