<?php

namespace eoko\modules\TabModule;

use eoko\module\HasJavascript;
//use eoko\module\Module;
use eoko\module\HasTitle;
use eoko\modules\TreeMenu\HasMenuActions;
use eoko\modules\TreeMenu\ActionProvider\ModuleProvider;

use eoko\template\Template;

/**
 * Base class for modules with a main tab.
 * 
 * This module provides base functionnalities to create the tab and open
 * it in the main destination.
 * 
 * 
 * Configuration
 * -------------
 * 
 * ### config:
 * 
 * The `config` node will be included in the generated javascript module (that
 * is, the js module will have a `config` property which value will be the 
 * content of the `config` config node converted to javascript).
 * 
 * #### config.tab:
 * 
 * The `config.tab` node will be applied to the tab configuration before it is
 * created.
 * 
 * If no title is provided in this fashion, the {@link eoko\modules\BaseModule\BaseModule}'s
 * configured title will be used as a default.
 * 
 * Example:
 * 
 *      # File: seasons.yml
 * 
 *      config:
 *        tab:
 *          title: Saisons
 * 
 * @package Modules\Eoko\TabModule
 * @author Éric Ortéga <eric@planysphere.fr>
 */
class TabModule extends _ implements HasJavascript {

	protected $defaultExecutor = 'js';

	protected function createJavascriptModuleProperties() {

		$config = $this->getConfig();

		$properties = array(
			'title' => $this->getTitle(),
			'iconCls' => $this->getIconCls(false),
			'tabChild' => $config->get('tabChild'),
		);

		$cfg = $config->get('config');
		if ($cfg) {
			$properties['config'] = $cfg;
		}

		return $properties;
	}

	public function createModuleJavascriptTemplate() {

		$config = $this->getConfig();
		$moduleName = $this->getName();

		$tpl = Template::create()->setFile($this->findFilenameInLineage('TabModule.js.php'));

		$tpl->namespace = $config->get('jsNamespace');
		$tpl->module = $moduleName;
		$tpl->parentClass = $config->get('jsParentClass');

		$tpl->properties = $this->createJavascriptModuleProperties($config);

		return $tpl;
	}

	private function findFilenameInLineage($pattern, $type = null) {
		foreach ($this->getParentNames(true) as $name) {
			$r = $this->searchPath(str_replace('%module%', $name, $pattern), $type);
			if (null !== $r) {
				return $r;
			}
		}
		throw new \eoko\file\CannotFindFileException($pattern);
	}

	public function getJavascriptAsString() {
		if ($this->getConfig()->getValue('private/generateJavascriptModule', true)) {
			if (!$this->isAbstract()
					|| $this->getConfig()->getValue('private/generateAbstractJavascriptModule', false)) {
				return $this->createModuleJavascriptTemplate()->render(true);
			}
		}
	}
}
