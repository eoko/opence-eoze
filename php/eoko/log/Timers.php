<?php

namespace eoko\log;

use \IllegalStateException;

class Timers {

	private $timers = array();

	/**
	 * Creates a new {@link Timer} for the specified $context and $key. If a
	 * Timer for the same $context and $key has already been created, an
	 * Exception will be thrown.
	 * @param string $context
	 * @param string $key
	 * @param string $message
	 * @return Timer
	 */
	public function create($context, $key, $message, $level) {
//		if (isset($this->timers[$context][$key])) {
//			throw new IllegalStateException("Timer $context:$key already created");
//		}
		return $this->timers[$context][$key] = new Timer($message, $level);
	}

	/**
	 * Retrieves the {@link Timer} for the specified $context and $key. If no
	 * such Timer has yet been created by {@link create()}, NULL will be
	 * returned.
	 * @param string $context
	 * @param string $key
	 * @return Timer
	 */
	public function get($context, $key) {
		if (isset($this->timers[$context][$key])) return $this->timers[$context][$key];
		else return null;
	}

}
