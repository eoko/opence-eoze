<?php

namespace eoko\script;
use \Debug;

abstract class Script {

	private $log = true;

	public $executionTime;

	final public function start() {
		$startTime = microtime();
		$this->run();
		$this->executionTime = Debug::elapsedTime($startTime, microtime());
	}

	abstract protected function run();
}
