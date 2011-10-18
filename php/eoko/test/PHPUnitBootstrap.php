<?php

namespace eoko\test;

class PHPUnitBootstrap {
	
	private function __construct() {}
	
	private static $done = false;
	
	public static function bootstrap() {
		if (!self::$done) {
			self::$done = true;
			require_once 'PHPUnit.php';
			require_once __DIR__ . DIRECTORY_SEPARATOR . 'bootstrap.php';
			require_once __DIR__ . DIRECTORY_SEPARATOR . 'PhpUnit_DatabaseTestCase.class.php';
		}
	}
}

PHPUnitBootstrap::bootstrap();
