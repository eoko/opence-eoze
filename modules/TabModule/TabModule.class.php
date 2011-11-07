<?php

namespace eoko\modules\TabModule;

use eoko\_getModule\BaseModule as BaseModule;
//use eoko\module\Module;
use eoko\module\HasTitle;
use eoko\modules\TreeMenu\HasMenuActions;
use eoko\modules\TreeMenu\ActionProvider\ModuleProvider;

class TabModule extends BaseModule {
	
	protected $defaultExecutor = 'js';
	
}