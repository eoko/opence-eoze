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

if (false) {
	
$owd = getcwd();
if (!@chdir(DATABASE_DUMP_PATH)) {
	throw new SystemException("Database Dump Abort: Directory does not exists: " . DATABASE_DUMP_PATH);
}

try {
	$params = eoko\database\Database::getDefaultAdapter()->getConfig();
	$out = 'db.sql.gz';
	$fn();
} catch (Exception $ex) {
	chdir($owd);
	echo $ex->getMessage() . PHP_EOL;
	die;
}

function dbDump() {

	global $params, $out;

	echo "\n\nDumping database to " . getcwd() . "$out...\n";
	
	if (file_exists($out)) unlink($out);

	$cmd = "mysqldump --user $params[user] --password=$params[password] --opt $params[database] | gzip > $out";
	system($cmd);

	if (!file_exists($out)) {
		throw new Exception('Error with dumping the database!');
	}
}

function dbLoad() {
	global $params, $out;

	if (!file_exists($out)) {
		throw new Exception('Missing dump file: ' . $out);
	}

	$cmd = "gunzip < $out | mysql --user $params[user] --password=$params[password] $params[database]";
	system($cmd);
}

}
