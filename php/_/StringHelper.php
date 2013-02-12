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
		$quotedNeedle = preg_quote($needle, '/');
		foreach ($replaces as $replace) {
			$subject = preg_replace("/$quotedNeedle/", $replace, $subject, 1);
		}
		return $subject;
	}
}
