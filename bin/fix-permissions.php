#!usr/bin/php
<?php

require_once 'init.inc.php';

$dirs = array(
	LOG_PATH,
	CACHE_PATH,
	EXPORTS_PATH,
	MODEL_PATH,
	MODEL_BASE_PATH,
	MODEL_PROXY_PATH,
	MODEL_QUERY_PATH,
);


$phpUser = 'www-data';
$answer = false;

while (!$answer) {
	echo 'PHP user group is "' . $phpUser . '". Change [y/N] ? ';
	$answer = trim(strtolower(fgets(STDIN)));
	if ($answer == '') {
		$answer = 'n';
	} else if ($answer != 'n' && $answer != 'y') {
		$answer = false;
	}
}

if ($answer == 'y') {
	echo 'PHP user: ';
	$phpUser = trim(fgets(STDIN));
}

foreach ($dirs as $dir) {

	if (!file_exists($dir)) {
		echo "WARNING: directory does not exist: $dir\n";
		return;
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
