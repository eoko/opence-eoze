<?php

namespace eoko\module\executor;

use eoko\util\HttpResponse;

abstract class ExecutorBase extends Executor {

	public function answer404($die = true) {
		HttpResponse::answer404($die);
	}
}