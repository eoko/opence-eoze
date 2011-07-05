<?php

namespace eoko\file;

class Paths {

	const ROOT = ROOT;
	const APP_ROOT = APP_ROOT;

	private static $vars = array(
		'lib' => PHP_ROOT,
		'app' => APP_ROOT,
	);

	private function __construct() {}

	public static function convertConfigPath($path) {

	}
}