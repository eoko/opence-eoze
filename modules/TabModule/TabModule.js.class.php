<?php

namespace eoko\modules\TabModule;

use eoko\module\executor\JsonExecutor;
use eoko\template\Template;
use eoko\file\FileType;

use SystemException, MissingConfigurationException;

class Js extends \eoko\module\executor\ExecutorBase {
	
	protected function processResult($result) {
		if (is_string($result)) {
			require $result;
		} else if ($result instanceof Template) {
			$result->render();
		} else if ($result === false) {
			throw new SystemException('Unknown error');
		}
	}
	
	public function get_module() {
		
		$module = $this->getModule();
		$config = $module->getConfig();
		$moduleName = $module->getName();
		
		$wrapper = $config->get('wrapper');
		$main = $config->get('main');
		
		if (!$wrapper) {
			throw new MissingConfigurationException(
					$this->getName() . '.yml', 'wrapper');
		}
		
		$wrapper = str_replace('%module%', $moduleName, $wrapper);
		$wrapper = $this->findPath($wrapper);
		$tpl = Template::create()->setFile($wrapper);
		
		$tpl->namespace = $config->get('jsNamespace');
		$tpl->module = $moduleName;
		$tpl->var = $config->get('moduleJsVarName');
		$tpl->iconCls = $module->getIconCls(false);
		
		$cfg = $config->get('config');
		if ($cfg) {
			$tpl->config = json_encode($cfg);
		}
		
		if ($main) {
			$main = str_replace('%module%', $moduleName, $main);
			$main = $this->findPath($main);
			$tpl->main = file_get_contents($main);
		}
		
		return $tpl;
	}
}