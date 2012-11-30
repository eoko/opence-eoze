<?php

call_user_func(function() {
	$ds = DIRECTORY_SEPARATOR;
	$pp = "$ds..";
	chdir(__DIR__ . $pp . $pp . $ds . 'tests' . $ds . 'features');

	global $argv;

	$cmd = 'behat';

	if (in_array('--colors', $argv)) {
		$cmd .= ' --colors';
	}
	if (in_array('-v', $argv) || in_array('--verbose', $argv)) {
		$cmd .= ' --verbose';
	}

	system($cmd);
});
