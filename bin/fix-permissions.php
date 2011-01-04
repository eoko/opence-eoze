#!usr/bin/php
<?php

require_once 'init.inc.php';

$dirs = array(
	LOG_PATH,
	CACHE_PATH,
	EXPORTS_PATH,
);

$required_dirs = array(
	MODEL_PATH,
	MODEL_BASE_PATH,
	MODEL_PROXY_PATH,
	MODEL_QUERY_PATH,
);


$phpUser = 'www-data';
if (askYesNo('PHP user group is "' . $phpUser . '". Change')) {
	$phpUser = ask('PHP user: ');
}

foreach ($dirs as $dir) {
	fixDirectory($dir, false);
}
foreach ($required_dirs as $dir) {
	fixDirectory($dir, true);
}

function ask($question, $allowBlank = false) {
	while (!$answer) {
		$lc = substr($question, -1);
		if ($lc !== ' ' && $lc !== "\n") $question .= ' ';

		echo $question;
		$answer = trim(fgets(STDIN));
	}
	return $answer;
}

function askMC($question, $opts) {
	$ao = array();
	$default = null;
	for ($i=0, $l=strlen($opts); $i<$l; $i++) {
		$c = $opts[$i];
		$ao[$c] = true;
		if (strtoupper($c) === $c) {
			if ($default !== null) throw new IllegalArgumentException('Invalid option string: ' . $opts);
			$default = $c;
		}
	}
	while (1) {
		$answer = ask($question, $default !== null);
		if ($answer === '') {
			if ($default !== null) {
				return strtolower($default);
			} else if (array_key_exists($answer, $ao)) {
				return strtolower($answer);
			}
		} else {
			if (array_key_exists($answer, $ao)) {
				return strtolower($answer);
			}
		}
	}
}

function askYesNo($question, $default = false) {

	$lc = substr($question, -1);
	if ($lc !== ' ' && $lc !== "\n") $question .= ' ';

	if ($default !== null) {
		$opts = $default ? 'Y/n' : 'y/N';
	} else {
		$opts = 'y/n';
	}
	$question .= "[$opts] ? ";

	while (true) {
		echo $question;
		$answer = trim(strtolower(fgets(STDIN)));
		if ($answer == 'n') return false;
		else if ($answer == 'y') return true;
		else if ($answer == '' && $default !== null) return $default;
	}
}

$owner = null;
function createDirectory($dir) {
	global $owner;
	if ($owner === null) {
		$owner = ask('Owner ?');
	}

	Logger::get()->info('Creating directory {}', $dir);
	mkdir($dir, 0755, true);

	chown($dir, $owner);
}

function fixDirectory($dir, $createIf) {

	global $phpUser;

	if (!file_exists($dir)) {
		if ($createIf) {
			if (askYesNo("Directory $dir does not exists. Create", true)) {
				createDirectory($dir);
			}
		} else {
			echo "WARNING: directory does not exist: $dir\n";
			return;
		}
	}

	// File group
	if (chgrp($dir, $phpUser)) echo "'$dir' group changed to $phpUser\n";
	else echo "ERROR: Cannot change group for '$dir'\n";

	// File permission
	$perm = substr(sprintf('%o', fileperms($dir)), -4);

	if ((int) $perm[2] < 7) {
		$perm = octdec($perm[0] . $perm[1] . '7' . $perm[2]);
		if (chmod($dir, $perm)) {
			clearstatcache();
			$perm = substr(sprintf('%o', fileperms($dir)), -4);
			echo "'$dir' permission changed to $perm\n";
		} else {
			echo "ERROR: Cannot change permissions for '$dir'\n";
		}
	} else {
		echo "Group already has write-access on '$dir'.\n";
	}
}