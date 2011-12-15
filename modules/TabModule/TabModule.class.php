<?php

namespace eoko\modules\TabModule;

use eoko\_getModule\BaseModule as BaseModule;
//use eoko\module\Module;
use eoko\module\HasTitle;
use eoko\modules\TreeMenu\HasMenuActions;
use eoko\modules\TreeMenu\ActionProvider\ModuleProvider;

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
class TabModule extends BaseModule {
	
	protected $defaultExecutor = 'js';
	
}