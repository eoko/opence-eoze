<?php

define('ADD_LOG_APPENDERS', false);

define('PROPER_ENTRY', true);

define('ROOT', realpath(dirname(__FILE__) . '/../..') . DIRECTORY_SEPARATOR);
$is_script = true;

require_once realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..')
	. DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . 'init.inc.php';

Logger::removeAllAppender();
Logger::setDefaultContext('');
Logger::addAppender(new LoggerOutputAppender(false));

$dumpPre = null;
$endDumpPre = "\n";

ExtJSResponse::setEnabled(false);

if (ob_get_status()) {
	ob_end_flush();
}
