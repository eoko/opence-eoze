<?php

require_once 'init.inc.php';
Logger::addAppender(new LoggerOutputAppender(false));

use eoko\util\YmlReader;

class Test {
	private function doRun() {
		echo 'Test';
	}
	public function run() {
		$this->doRun();
	}
}

class TTest extends Test {
	private function doRun() {
		echo 'T-Test';
	}
	public function run() {
		$this->doRun();
	}
}

$t = new TTEst;
$t->run();