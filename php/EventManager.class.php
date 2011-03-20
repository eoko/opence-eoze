<?php

class EventManager {

	protected $listeners = array();

	public function fire($event) {
		if (!isset($this->listeners[$event])) return; // no listeners registered
		if (func_num_args() > 1) {
			$params = func_get_args();
			$params = array_slice($params, 1);
			foreach ($this->listeners[$event] as $l) {
				$args = $l[1] === null ? $params : array_merge($l[1], $params);
				call_user_func_array($l[0], $args);
			}
		} else {
			foreach ($this->listeners[$event] as $l) {
				if ($l[1] !== null) {
					call_user_func_array($l[0], $l[1]);
				} else {
					call_user_func($l[0]);
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
}