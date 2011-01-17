<?php

require_once 'init.inc.php';

function dieUsage() {
	echo 'Usage: sync-db ACTION' . PHP_EOL . 'ACTION    dump|load' . PHP_EOL;
	die;
}

if ($argc !== 2) dieUsage();
else $arg = $argv[1];

if ($arg === 'dump') $fn = 'dbDump';
else if ($arg === 'load') $fn = 'dbLoad';
else dieUsage();

$owd = getcwd();
chdir(DATABASE_DUMP_PATH);
try {
	$params = eoko\database\ConnectionManager::getParams();
	$out = 'db.sql.gz';
	$fn();
} catch (Exception $ex) {
	chdir($owd);
	echo $ex->getMessage() . PHP_EOL;
	die;
}

function dbDump() {

	global $params, $out;
	
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

	$cmd = "mysql --user $params[user] --password=$params[password] $params[database] < $out";
}
