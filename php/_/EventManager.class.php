<?php

class EventManager {

	protected $listeners = array();
	
	/**
	 * Listeners that will be fire only once.
	 * @var array
	 */
	protected $waiters = array();

	public function fire($event) {
		if (isset($this->listeners[$event])) {
			$listeners = $this->listeners[$event];
		} else {
			$listeners = array();
		}
		if (isset($this->waiters[$event])) {
			$listeners = array_merge($listeners, $this->waiters[$event]);
			unset($this->waiters[$event]);
		}
		if ($listeners) {
			if (func_num_args() > 1) {
				$params = func_get_args();
				$params = array_slice($params, 1);
				foreach ($listeners as $l) {
					$args = $l[1] === null ? $params : array_merge($l[1], $params);
					call_user_func_array($l[0], $args);
				}
			} else {
				foreach ($listeners as $l) {
					if ($l[1] !== null) {
						call_user_func_array($l[0], $l[1]);
					} else {
						call_user_func($l[0]);
					}
				}
			}
		}			
	}

	public function on($eventName, $callback, $extraArgs = null) {
		if (!isset($this->listeners[$eventName])) {
			$this->listeners[$eventName] = array();
		}
		$this->listeners[$eventName][] = array(
			0 => $callback, 1 => $extraArgs
		);
	}
	
	public function onOnce($eventName, $callback, $extraArgs = null) {
		if (!isset($this->waiters[$eventName])) {
			$this->waiters[$eventName] = array();
		}
		$this->waiters[$eventName][] = array(
			0 => $callback, 1 => $extraArgs
		);
	}
}