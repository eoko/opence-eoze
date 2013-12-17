<?php

class Html {

	private function __construct() {}

	public static function concatParams($params, $quote="&quote;") {
		if (!$params || !count($params))
			return null;

		$r = array();
		foreach ($params as $name => $value) {
			if ($quote !== false) $value = str_replace('"', $quote);
			$r[] = <<<PARAM
$name="$value"
PARAM;
		}

		return implode(' ', $r);
	}
}
