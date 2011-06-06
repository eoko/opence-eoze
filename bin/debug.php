<?php

require_once 'init.inc.php';
//Logger::addAppender(new LoggerOutputAppender(false));

Logger::getLogger()->setLevel(Logger::ERROR);
Logger::getLogger('eoko\cache\Cache')->setLevel(Logger::DEBUG);

use eoko\module\ModuleManager;

$m = ModuleManager::getModule('sm_child_48_products');

//dump($m->getConfig());
$x = $m->getConfig()->get('extra');

dumpl($x['menu']);

//dump($m->getActionProvider()->getFamily());