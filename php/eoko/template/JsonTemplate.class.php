<?php

namespace eoko\template;

use \ExtJSResponse;
use eoko\util\Json;

class JsonTemplate extends Template {
	
	protected function doRender() {
//		ExtJSResponse::mergeIn($this->vars);
		if (!headers_sent()) {
			header('Content-type: application/json');
		}
		echo Json::encode($this->vars);
	}
}