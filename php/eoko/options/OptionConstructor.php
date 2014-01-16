<?php

namespace eoko\options;

class OptionConstructor {

	public function __construct($options = null) {
		if ($options) {
			foreach ($options as $k => $v) {
				if (method_exists($this, $m = 'set' . ucfirst($k))) {
					$this->$m($v);
				} else {
					$this->$k = $v;
				}
			}
		}
	}
}
