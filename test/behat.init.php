<?php

call_user_func(function() {
	
	$ds = DIRECTORY_SEPARATOR;
	
	$featureNamespace = 'eoze\\behat\\context\\';
	$strlenFNS = strlen($featureNamespace);
	
	$contextPath = __DIR__ . $ds . 'behat' . $ds;
	define('EOZE_FEATURES_PATH', $contextPath);

	// Register autoload for eoze Context classes
	spl_autoload_register(function($class) use($ds, $contextPath, $featureNamespace, $strlenFNS) {
		if (substr($class, 0, $strlenFNS) === $featureNamespace) {
			$classPath = str_replace('\\', $ds, substr($class, $strlenFNS));
			if (file_exists($file = $contextPath . $classPath . '.php')
					|| file_exists($file = $contextPath . $classPath . 'Context.php')) {
				require $file;
			}
		}
	});
	
	require __DIR__ . $ds . 'php' . $ds . 'init.inc.php';

	// PHPUnit
	require_once 'PHPUnit/Autoload.php';
	require_once 'PHPUnit/Framework/Assert/Functions.php';
	
	class_alias('Behat\Behat\Exception\PendingException', 'PendingException');
	class_alias('Behat\Behat\Exception\PendingException', $featureNamespace . 'PendingException');
});