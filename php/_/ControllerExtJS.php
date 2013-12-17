<?php

class ControllerExtJS extends ModuleController {

	/** @var ExtJSResponse */
	protected $response;

	public function __construct($controllerName, $action, Request &$request) {
		parent::__construct($controllerName, $action, $request);
		$this->response = ExtJSResponse::getProxy();
	}
}