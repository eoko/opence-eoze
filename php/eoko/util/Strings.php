<?php

namespace eoko\util;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 2 déc. 2011
 */
class Strings {
	
	private function __construct() {}
	
	private static $slugReplace = array(
		'à' => 'a',
		'Â' => 'a',
		'Ä' => 'a',
		'é' => 'e',
		'è' => 'e',
		'ê' => 'e',
		'ë' => 'e',
		'É' => 'E',
		'È' => 'E',
		'Ê' => 'E',
		'Ë' => 'E',
		'î' => 'i',
		'ï' => 'i',
		'Ï' => 'I',
		'Î' => 'I',
		'ô' => 'o',
		'ö' => 'o',
		'Ô' => 'O',
		'Ö' => 'O',
		'û' => 'u',
		'Ü' => 'u',
		'Ü' => 'U',
		'Û' => 'U',
		'ÿ' => 'y',
		'Ÿ' => 'Y',
		'æ' => 'ae',
		'Æ' => 'AE',
		'œ' => 'oe',
		'Œ' => 'OE',
	);
	
	public static function slugify($text) {
		
		$text = str_replace(array_keys(self::$slugReplace), self::$slugReplace, $text);

		// replace non letter or digits by -
		$text = preg_replace('~[^\\pL\d]+~u', '-', $text);

		// trim
		$text = trim($text, '-');
		
		// transliterate
		$text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

		// lowercase
		$text = strtolower($text);

		// remove unwanted characters
		$text = preg_replace('~[^-\w]+~', '', $text);

		if (empty($text)) {
			return 'n-a';
		}

		return $text;
	}
}
