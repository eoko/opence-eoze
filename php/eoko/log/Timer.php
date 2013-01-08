<?php

namespace eoko\log;

use \IllegalStateException;

class Timer {

	const MICRO  = 1;
	const SECOND = 1000;

	private $start = null, $end = null;
	private $level, $message = null;

	public function __construct($message, $level) {
		$this->message = $message;
		$this->level = $level;
	}

	public function start() {
		$this->start = microtime(true);
	}

	public function stop() {
		$this->end = microtime(true);
		if ($this->start === null && !$this->parent) {
			throw new IllegalStateException('Timer has not been started');
		}
	}

	public function getTime() {
		if ($this->start === null) {
			throw new IllegalStateException('Timer has not been started');
		}
		if ($this->end === null) {
			throw new IllegalStateException('Timer is still running');
		}
		$time = $this->end - $this->start;

		if ($time > 1000) {
			return sprintf('%.4fs', $time);
		} else {
			return sprintf('%.0fms', $time*1000);
		}
	}

	public function log(Logger $logger, $context, $key) {
		$msg = $this->message ? $this->message : "$context - $key completed in {}";
		$logger->log($this->level, $msg, $this->getTime());
	}
}