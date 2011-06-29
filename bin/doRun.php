<?php

if (!isset($_SERVER['SCRIPT_NAME'])) {
	die;
}

if (!isset($class)) {
	if ($argc !== 2) {
		echo 'Usage: ./run SCRIPT_CLASS_NAME' . PHP_EOL;
		exit(-1);
	} else {
		$class = $argv[1];
	}
}

// ---- INIT -------------------------------------------------------------------

require_once 'init.inc.php';

$script = new $class();
$script->start();