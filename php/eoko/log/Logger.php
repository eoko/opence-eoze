<?php

namespace eoko\log;

use Logger as Base;

class Logger extends Base {

	private $timers = null;

	private function getTimers() {
		if (!$this->timers) $this->timers = new Timers();
		return $this->timers;
	}

	public function startTimer($key, $message, $level = self::DEBUG) {
		if (!$this->isActive($level)) {
			return;
		}
		$this->getTimers()->create($this->context, $key, $message, $level)
				->start();
	}

	public function stopTimer($key) {
		if ($this->timers && (null !== $timer = $this->timers->get($this->context, $key))) {
			$timer->stop();
			$timer->log($this, $this->context, $key);
		}
	}
}
