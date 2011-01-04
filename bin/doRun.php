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
//define('ADD_LOG_APPENDERS', false);
//
//$directAccess = false;
//define('PROPER_ENTRY', true);
//
//$phpPath = realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..')
//	. DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR;
//
//require_once $phpPath . 'init.inc.php';
//
//Logger::removeAllAppender('LoggerFirePHPAppender');
//Logger::setDefaultContext('');
//Logger::addAppender(new LoggerOutputAppender(false));
//
//$dumpPre = null;
//$endDumpPre = "\n";
//
//ExtJSResponse::setEnabled(false);
//
//ob_end_flush();

// -----------------------------------------------------------------------------

$script = new $class();
$script->start();