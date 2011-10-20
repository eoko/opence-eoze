<?php

if (($_SERVER && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__)
		|| (!$_SERVER && php_sapi_name() !== 'cli')) {

	exit('Illegal entry point');
}

if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);

call_user_func(function() {
	$eoze = realpath(dirname(__FILE__) . DS . '..') . DS;
	$root = realpath(dirname(__FILE__) . DS . '..' . DS . '..') . DS;

	$ds = DS;

	require_once "$eoze{$ds}php{$ds}Context.class.php";

	$context = new eoko\context\ContextBase();

	$context->rootPath = $root;

	$context->baseUrl = 'http://' .
			(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost')
			. rtrim(dirname($_SERVER['PHP_SELF']) , '/\\') . '/' ;

	$context->eoze = new eoko\context\Eoze(array(
		'namespace' => 'eoko',
		'path' => "{$eoze}php$ds",
	));

	global $directAccess;
	require_once "{$eoze}php{$ds}init.inc.php";
});
