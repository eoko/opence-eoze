<?php

namespace eoko\util;

class HttpResponse {

	private function __construct() {}

	public static function answer404($die = true) {
		header('404 Not Found');
		echo '<h1>404 Not Found</h1>';
		if ($die) die;
	}
}
