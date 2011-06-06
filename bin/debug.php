<?php

require_once 'init.inc.php';
//Logger::addAppender(new LoggerOutputAppender(false));

Logger::getLogger()->setLevel(Logger::ERROR);

use eoko\module\ModuleManager;

$m = ModuleManager::getModule('sm_child_48_products');

$x = $m->getConfig()->get('extra');

//dump(isset($x['menu']));

dump($m->getActionProvider()->getFamily());