<?php

namespace eoko\template;

use \ExtJSResponse;
use eoko\util\Json;

use eoko\output\Output;

class JsonTemplate extends Template {

	protected function doRender() {
		if (!headers_sent()) {
			header('Content-type: application/json');
		}
		Output::out(Json::encode($this->vars));
	}
}
