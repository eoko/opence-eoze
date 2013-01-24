<?php

namespace eoko\cqlix;

use eoko\cqlix\generator\Generator;
use eoko\plugin\PluginManager;

/**
 * @todo PHPUnit Test
 * If myModel exists, then myModel refers to myModel, else it is aliased to
 * Model. Same goes for myModelTable and ModelTable, etc.
 */

foreach (array(
	'myModel' => 'Model',
	'myModelTable' => 'ModelTable',
	'myQuery' => 'ModelTableQuery',
) as $alias => $class) {
	if (!class_exists($alias)) class_alias($class, $alias);
}
