<?php

namespace eoko\module\executor;

class ExtJsExecutor extends ExecutorBase {

	protected $successParamName = 'success';

	private $response = array();

	protected function processResult($result) {
		if (is_bool($result)) {
			$this->response[$this->successParamName] = $result;
			$this->answer($this->response);
		}
	}

	private function answer($response) {
		echo Json::encode($response);
	}

	public function __set($name, $value) {
		$this->response[$name] = $value;
	}

	public function __get($name) {
		return $this->response[$name];
	}

	public function __isset($name) {
		return isset($this->response[$name]);
	}

	public function __unset($name) {
		unset($this->response[$name]);
	}
}
