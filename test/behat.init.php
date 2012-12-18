<?php

call_user_func(function() {

	$featureNamespace = 'eoze\\behat\\context\\';
	$strlenFNS = strlen($featureNamespace);

	$contextPath = __DIR__ . '/behat/';
	define('EOZE_FEATURES_PATH', $contextPath);

	// Register autoload for eoze Context classes
	spl_autoload_register(function($class) use($contextPath, $featureNamespace, $strlenFNS) {
		if (substr($class, 0, $strlenFNS) === $featureNamespace) {
			$classPath = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, $strlenFNS));
			if (file_exists($file = $contextPath . $classPath . '.php')
					|| file_exists($file = $contextPath . $classPath . 'Context.php')) {
				require $file;
			}
		}
	});

	require __DIR__ . '/php/init.inc.php';

	// PHPUnit
	require_once 'PHPUnit/Autoload.php';
	require_once 'PHPUnit/Framework/Assert/Functions.php';

	foreach (array('', $featureNamespace) as $ns) {
		class_alias('Behat\Behat\Exception\PendingException', $ns . 'PendingException');
		class_alias('Behat\Gherkin\Node\TableNode', $ns . 'TableNode');
	}
});
