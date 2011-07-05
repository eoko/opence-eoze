<?php

class StringHelper {

	private function __construct() {}

	private static $randStringCharacters = array(
		'AZ' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
		'az' => 'abcdefghijklmnopqrstuvwxyz',
		'num' => '0123456789',
		'azAZ' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz',
		'alphanum' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789',
		'alphanum_ci' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',
	);

	public static function randomString($length = 8, $characters = 'alphanum', $customChars = false) {
		if (!$customChars) $characters = self::$randStringCharacters[$characters];
		$max = strlen($characters) - 1;
	    $r = array();
		for ($p = 0; $p < $length; $p++) {
			$r[] = $characters[mt_rand(0, $max)];
		}
		return implode('', $r);
	}
	
	public static function replaceSuccessively($needle, $replaces, $subject) {
		$nExpected = count($replaces);
		$r = str_replace(
			array_fill(0, $nExpected, $needle), 
			$replaces, 
			$subject,
			$n
		);
		if ($n !== $nExpected) {
			throw new IllegalStateException(
				"Invalid number of replacement strings (given: $nExpected, replacements: $n)"
			);
		}
		return $r;
	}
}