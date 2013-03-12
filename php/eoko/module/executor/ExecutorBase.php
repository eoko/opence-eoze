<?php

namespace eoko\module\executor;

use Zend\Http\Response;

abstract class ExecutorBase extends Executor {

	public function answer404($die = true) {
		$this->getResponse()->setStatusCode(Response::STATUS_CODE_404);
	}
}
