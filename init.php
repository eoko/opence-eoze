#!/usr/bin/php
<?php

if (php_sapi_name() !== 'cli') {
	echo 'Illegal access. For security reasons, this script can only be run from the command line.';
	exit(-1);
}

require_once 'bin/init.inc.php';

$script = array(
	'quiet' => true,
);

function println($s) { echo $s . PHP_EOL; }

println('Checking permissions...');

require 'bin/fix-permissions.php';