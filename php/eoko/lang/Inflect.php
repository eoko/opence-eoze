<?php

namespace eoko\lang;

use org\kuwamoto\Inflect as BaseInflect;

require_once __DIR__ . '/org/kuwamoto/Inflect.php';

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 22 nov. 2011
 */
class Inflect {
	
	public static function pluralize($string) {
		return BaseInflect::pluralize($string);
	}
	
	public static function singularize($string) {
		return BaseInflect::singularize($string);
	}
	
	public static function pluralizeIf($string, $count) {
		return BaseInflect::pluralize_if($count, $string);
	}
	
}
