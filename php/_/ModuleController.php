<?php

class ModuleController extends Controller {

	/**
	 * Action: configure module. Load the GridModule's configuration and send
	 * it to the client.
	 */
	public function configure_module() {

		$module = $this->request->get('module', null);
		$config = $this->getModuleConfiguration($module);
		if ($config === null) $config = array();

		ExtJSResponse::put('config', $config);
		ExtJSResponse::answer();
	}

	protected function getModuleConfiguration($name = null) {
		return null;
	}
}