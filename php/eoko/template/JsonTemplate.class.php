<?php

namespace eoko\template;

use \ExtJSResponse;
use eoko\util\Json;

class JsonTemplate extends Template {
	
	protected function doRender() {
//		ExtJSResponse::mergeIn($this->vars);
		echo Json::encode($this->vars);
	}
}