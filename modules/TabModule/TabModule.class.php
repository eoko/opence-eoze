<?php

namespace eoko\modules\TabModule;

use eoko\_getModule\BaseModule as BaseModule;
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
class TabModule extends BaseModule implements HasJavascript {
	
	protected $defaultExecutor = 'js';
	
	public function createModuleJavascriptTemplate() {
		
		$config = $this->getConfig();
		$moduleName = $this->getName();
		
		$wrapper = $config->get('wrapper');
		$main = $config->get('main');
		
		if (!$wrapper) {
			throw new MissingConfigurationException(
					$this->getName() . '.yml', 'wrapper');
		}
		
		$tpl = Template::create()->setFile($this->findFilenameInLineage($wrapper));
		
		$tpl->namespace = $config->get('jsNamespace');
		$tpl->module = $moduleName;
		$tpl->var = $config->get('moduleJsVarName');
		$tpl->iconCls = $this->getIconCls(false);
		$tpl->title = $this->getTitle();
		
		$cfg = $config->get('config');
		if ($cfg) {
			$tpl->config = json_encode($cfg);
		}
		
		if ($main) {
			$tpl->main = Template::create()->setFile($this->findFilenameInLineage($main, array(
				'js', 'js.php'
			)));
		}
		
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
		if (!$this->isAbstract()) {
			return $this->createModuleJavascriptTemplate()->render(true);
		}
	}
}