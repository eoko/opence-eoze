<?php

require_once 'init.inc.php';

use eoko\database\Database;

function dieUsage() {
	echo 'Usage: db-sync ACTION' . PHP_EOL . 'ACTION    dump|load' . PHP_EOL;
	die;
}

if (isset($syncDbAction)) {
	$arg = $syncDbAction;
} else {
	if ($argc !== 2) dieUsage();
	else $arg = $argv[1];
}

$dumper = Database::getDefaultAdapter()->getDumper();

// Untested modifications...
if ($arg === 'dump') {
//	$fn = 'dbDump';
	$dumper->dump(DATABASE_DUMP_PATH . 'data.sql.gz', DATABASE_DUMP_PATH . 'structure.sql.gz');
} else if ($arg === 'load') {
//	$fn = 'dbLoad';
	$dumper->load(DATABASE_DUMP_PATH . 'data.sql.gz');
} else {
	dieUsage();
}
